<?php

namespace Tests\Unit;

use App\Services\Siakad\UnwApiSiakad;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UnwApiSiakadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set([
            'services.unwapi_siakad.base' => 'https://siakad.test',
            'services.unwapi_siakad.email' => 'edom@example.test',
            'services.unwapi_siakad.password' => 'secret',
            'services.unwapi_siakad.token_cache_key' => 'test_unwapi_siakad_token',
            'services.unwapi_siakad.token_cache_hours' => 12,
            'services.unwapi_siakad.verify_ssl' => true,
        ]);

        Cache::clear();
    }

    public function test_krs_logs_in_once_reuses_the_bearer_token_and_returns_data(): void
    {
        $section = $this->section();
        $loginCalls = 0;

        Http::fake(function (Request $request) use (&$loginCalls, $section) {
            if ($request->url() === 'https://siakad.test/login') {
                $loginCalls++;

                return Http::response(['data' => ['token' => 'token-1']]);
            }

            if (str_starts_with($request->url(), 'https://siakad.test/edom/krs')) {
                return Http::response(['data' => [$section]]);
            }

            return Http::response([], 404);
        });

        $api = app(UnwApiSiakad::class);

        $this->assertSame([$section], $api->krs(18273, 2026, 2));
        $this->assertSame([$section], $api->krs(18273, 2026, 2));
        $this->assertSame(1, $loginCalls);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://siakad.test/login'
            && $request['email'] === 'edom@example.test'
            && $request['password'] === 'secret');

        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://siakad.test/edom/krs')
            && $request->hasHeader('Authorization', 'Bearer token-1')
            && (int) $request['siakad_idmahasiswa'] === 18273
            && (int) $request['siakad_idtahunajaran'] === 2026
            && (int) $request['siakad_idsemester'] === 2
            && ! array_key_exists('id_unw_program_studi', $request->data()));

        Http::assertSentCount(3);
    }

    public function test_krs_rethrows_api_failure_without_calling_penawaran(): void
    {
        Http::fake(function (Request $request) {
            if ($request->url() === 'https://siakad.test/login') {
                return Http::response(['data' => ['token' => 'token-1']]);
            }

            if (str_starts_with($request->url(), 'https://siakad.test/edom/krs')) {
                return Http::response(['message' => 'KRS unavailable'], 500);
            }

            return Http::response([], 404);
        });

        $exception = null;

        try {
            app(UnwApiSiakad::class)->krs(18273, 2026, 2);
        } catch (RequestException $requestException) {
            $exception = $requestException;
        }

        $this->assertInstanceOf(RequestException::class, $exception);
        $this->assertSame(500, $exception->response->status());
        Http::assertNotSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://siakad.test/edom/penawaran'));
        Http::assertSentCount(2);
    }

    public function test_a_401_forgets_the_cached_token_and_retries_once(): void
    {
        $section = $this->section();
        $loginCalls = 0;
        $krsCalls = 0;

        Http::fake(function (Request $request) use (&$loginCalls, &$krsCalls, $section) {
            if ($request->url() === 'https://siakad.test/login') {
                $loginCalls++;

                return Http::response(['data' => ['token' => "token-{$loginCalls}"]]);
            }

            if (str_starts_with($request->url(), 'https://siakad.test/edom/krs')) {
                $krsCalls++;

                return $krsCalls === 1
                    ? Http::response([], 401)
                    : Http::response(['data' => [$section]]);
            }

            return Http::response([], 404);
        });

        $this->assertSame([$section], app(UnwApiSiakad::class)->krs(18273, 2026, 2));
        $this->assertSame(2, $loginCalls);
        $this->assertSame(2, $krsCalls);

        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://siakad.test/edom/krs')
            && $request->hasHeader('Authorization', 'Bearer token-1'));
        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://siakad.test/edom/krs')
            && $request->hasHeader('Authorization', 'Bearer token-2'));
        Http::assertSentCount(4);
    }

    public function test_mahasiswa_uses_the_cached_bearer_token_and_repeats_array_query_keys(): void
    {
        $profiles = [
            ['siakad_idmahasiswa' => 18273, 'npm' => '22.01.0001', 'nama' => 'Dimas Mahasiswa'],
            ['siakad_idmahasiswa' => 18274, 'npm' => '22.01.0002', 'nama' => 'Mahasiswa Kedua'],
        ];

        Http::fake([
            'https://siakad.test/login' => Http::response(['data' => ['token' => 'token-1']]),
            'https://siakad.test/edom/mahasiswa*' => Http::response(['data' => $profiles]),
        ]);

        $this->assertSame(
            $profiles,
            app(UnwApiSiakad::class)->mahasiswa([18273, 18274])
        );

        Http::assertSent(fn (Request $request): bool => str_starts_with(
            $request->url(),
            'https://siakad.test/edom/mahasiswa'
        )
            && $request['siakad_idmahasiswa'] === [18273, 18274]
            && $request->hasHeader('Authorization', 'Bearer token-1'));
        Http::assertSentCount(2);
    }

    public function test_semester_krs_and_mahasiswa_share_one_backend_login_token(): void
    {
        $loginCalls = 0;

        Http::fake(function (Request $request) use (&$loginCalls) {
            if ($request->url() === 'https://siakad.test/login') {
                $loginCalls++;

                return Http::response(['data' => ['token' => 'shared-token']]);
            }

            if ($request->url() === 'https://siakad.test/edom/semester') {
                return Http::response(['data' => [['id' => 2, 'nama' => 'Genap']]]);
            }

            if (str_starts_with($request->url(), 'https://siakad.test/edom/krs')) {
                return Http::response(['data' => [$this->section()]]);
            }

            if (str_starts_with($request->url(), 'https://siakad.test/edom/mahasiswa')) {
                return Http::response([
                    'data' => [[
                        'siakad_idmahasiswa' => 18273,
                        'npm' => '22.01.0001',
                        'nama' => 'Dimas Mahasiswa',
                    ]],
                ]);
            }

            return Http::response([], 404);
        });

        $api = app(UnwApiSiakad::class);

        $this->assertSame([['id' => 2, 'nama' => 'Genap']], $api->semester());
        $this->assertSame([$this->section()], $api->krs(18273, 2026, 2));
        $this->assertSame(
            [['siakad_idmahasiswa' => 18273, 'npm' => '22.01.0001', 'nama' => 'Dimas Mahasiswa']],
            $api->mahasiswa([18273])
        );
        $this->assertSame(1, $loginCalls);

        foreach (['semester', 'krs', 'mahasiswa'] as $path) {
            Http::assertSent(fn (Request $request): bool => str_starts_with(
                $request->url(),
                "https://siakad.test/edom/{$path}"
            )
                && $request->hasHeader('Authorization', 'Bearer shared-token'));
        }

        Http::assertSentCount(4);
    }

    private function section(): array
    {
        return [
            'idtawarmatakuliahdetail' => 4567,
            'idmatakuliah' => 123,
            'kode' => 'API101',
            'nama' => 'Mata Kuliah API',
            'dosen' => [
                'nidn' => '0612345678',
                'nama' => 'Dosen API',
            ],
            'dosen_team' => ['Dosen Pendamping API'],
        ];
    }
}
