<?php

namespace Tests\Unit;

use App\Services\Siakad\UnwApiSiakad;
use Illuminate\Http\Client\Request;
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
            'edom.fake_siakad.enabled' => false,
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
            && (int) $request['siakad_idsemester'] === 2);

        Http::assertSentCount(3);
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

    private function section(): array
    {
        return [
            'idtawarmatakuliahdetail' => 4567,
            'idmatakuliah' => 123,
            'kode' => 'TIF101',
            'nama' => 'Algoritma',
            'dosen' => [
                'nidn' => '0612345678',
                'nama' => 'Dosen Testing',
            ],
            'dosen_team' => ['Dosen Pendamping'],
            'id_unw_program_studi' => 14,
        ];
    }
}
