<?php

namespace App\Services\Siakad;

use App\Models\ProgramStudi;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class UnwApiSiakad
{
    private function token(): string
    {
        $cacheKey = (string) config('services.unwapi_siakad.token_cache_key', 'unwapi_siakad_token');
        $cacheHours = (int) config('services.unwapi_siakad.token_cache_hours', 12);

        return Cache::remember($cacheKey, now()->addHours($cacheHours), function (): string {
            $email = (string) config('services.unwapi_siakad.email');
            $password = (string) config('services.unwapi_siakad.password');

            if ($email === '' || $password === '') {
                throw new InvalidArgumentException('UNW_API_SIAKAD_EMAIL dan UNW_API_SIAKAD_PASSWORD harus diisi di .env.');
            }

            $response = $this->http()->asJson()
                ->acceptJson()
                ->post($this->baseUrl().'/login', [
                    'email' => $email,
                    'password' => $password,
                ])
                ->throw();

            $token = $response->json('data.token');

            if (! is_string($token) || $token === '') {
                throw new InvalidArgumentException('Response login unw-api-siakad tidak memiliki data.token.');
            }

            return $token;
        });
    }

    private function client(): PendingRequest
    {
        return $this->http()->withToken($this->token())
            ->acceptJson()
            ->baseUrl($this->baseUrl());
    }

    private function http(): PendingRequest
    {
        return Http::connectTimeout(10)
            ->timeout(30)
            ->withOptions([
                'verify' => filter_var(config('services.unwapi_siakad.verify_ssl', true), FILTER_VALIDATE_BOOLEAN),
            ]);
    }

    private function baseUrl(): string
    {
        $baseUrl = rtrim((string) config('services.unwapi_siakad.base'), '/');

        if ($baseUrl === '') {
            throw new InvalidArgumentException('UNW_API_SIAKAD_BASE_URL harus diisi di .env.');
        }

        return $baseUrl;
    }

    private function forgetToken(): void
    {
        Cache::forget((string) config('services.unwapi_siakad.token_cache_key', 'unwapi_siakad_token'));
    }

    private function request(string $method, string $path, array $payload = []): array
    {
        $response = $this->client()->{$method}($path, $payload);

        if ($response->status() === 401) {
            $this->forgetToken();
            $response = $this->client()->{$method}($path, $payload);
        }

        $json = $response->throw()->json('data');

        return is_array($json) ? $json : [];
    }

    public function semester(): array
    {
        return $this->request('get', '/edom/semester');
    }

    public function krs(
        int|string $siakadIdMahasiswa,
        int|string $siakadIdTahunAjaran,
        int|string $siakadIdSemester
    ): array {
        $payload = [
            'siakad_idmahasiswa' => (int) $siakadIdMahasiswa,
            'siakad_idtahunajaran' => (int) $siakadIdTahunAjaran,
            'siakad_idsemester' => (int) $siakadIdSemester,
        ];

        try {
            return $this->request('get', '/edom/krs', $payload);
        } catch (RequestException $exception) {
            if (! $this->isMissingProgramStudiColumnError($exception)) {
                throw $exception;
            }

            Log::warning('Endpoint /edom/krs SIAKAD gagal karena kolom ps.id_unw_program_studi tidak tersedia. Memakai fallback /edom/penawaran.', [
                'siakad_idmahasiswa' => $payload['siakad_idmahasiswa'],
                'siakad_idtahunajaran' => $payload['siakad_idtahunajaran'],
                'siakad_idsemester' => $payload['siakad_idsemester'],
                'message' => $exception->getMessage(),
            ]);

            return $this->fallbackKrsFromPenawaran($payload, $exception);
        }
    }

    public function penawaran(int|string $siakadIdTahunAjaran, int|string $siakadIdSemester, int|string|null $idUnwProgramStudi = null): array
    {
        $payload = [
            'siakad_idtahunajaran' => $siakadIdTahunAjaran,
            'siakad_idsemester' => $siakadIdSemester,
        ];

        if ($idUnwProgramStudi !== null && $idUnwProgramStudi !== '') {
            $payload['id_unw_program_studi'] = $idUnwProgramStudi;
        }

        return $this->request('get', '/edom/penawaran', $payload);
    }

    public function complete(int|string $siakadIdMahasiswa, int|string $siakadIdTahunAjaran, int|string $siakadIdSemester): array
    {
        return $this->request('post', '/edom/complete', [
            'siakad_idmahasiswa' => (int) $siakadIdMahasiswa,
            'siakad_idtahunajaran' => (int) $siakadIdTahunAjaran,
            'siakad_idsemester' => (int) $siakadIdSemester,
        ]);
    }

    public function openPeriod(int|string $siakadIdTahunAjaran, int|string $siakadIdSemester): array
    {
        return $this->request('post', '/edom/active-period', [
            'siakad_idtahunajaran' => $siakadIdTahunAjaran,
            'siakad_idsemester' => $siakadIdSemester,
        ]);
    }

    public function closePeriod(int|string $siakadIdTahunAjaran, int|string $siakadIdSemester): array
    {
        return $this->request('delete', '/edom/active-period', [
            'siakad_idtahunajaran' => $siakadIdTahunAjaran,
            'siakad_idsemester' => $siakadIdSemester,
        ]);
    }

    public function mahasiswa(array $siakadIdMahasiswa): array
    {
        $studentIds = collect($siakadIdMahasiswa)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return $this->request('get', '/edom/mahasiswa', [
            'siakad_idmahasiswa' => $studentIds,
        ]);
    }

    private function fallbackKrsFromPenawaran(array $payload, RequestException $originalException): array
    {
        $sections = [];
        $lastException = $originalException;

        foreach ($this->localProgramStudiIds() as $idUnwProgramStudi) {
            try {
                $sections = array_merge($sections, $this->penawaran(
                    $payload['siakad_idtahunajaran'],
                    $payload['siakad_idsemester'],
                    $idUnwProgramStudi
                ));
            } catch (RequestException $exception) {
                $lastException = $exception;

                if (! $this->isMissingProgramStudiColumnError($exception)) {
                    throw $exception;
                }

                Log::warning('Fallback /edom/penawaran dengan id_unw_program_studi lokal gagal.', [
                    'siakad_idtahunajaran' => $payload['siakad_idtahunajaran'],
                    'siakad_idsemester' => $payload['siakad_idsemester'],
                    'id_unw_program_studi' => $idUnwProgramStudi,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        $sections = $this->uniqueSections($sections);

        if ($sections !== []) {
            return $sections;
        }

        try {
            return $this->penawaran(
                $payload['siakad_idtahunajaran'],
                $payload['siakad_idsemester']
            );
        } catch (RequestException $exception) {
            $lastException = $exception;

            if (! $this->isMissingProgramStudiColumnError($exception)) {
                throw $exception;
            }

            Log::warning('Fallback /edom/penawaran tanpa id_unw_program_studi gagal.', [
                'siakad_idtahunajaran' => $payload['siakad_idtahunajaran'],
                'siakad_idsemester' => $payload['siakad_idsemester'],
                'message' => $exception->getMessage(),
            ]);
        }

        throw $lastException;
    }

    /**
     * @return array<int, int>
     */
    private function localProgramStudiIds(): array
    {
        try {
            return ProgramStudi::query()
                ->whereNotNull('id_unw_program_studi')
                ->orderBy('id_unw_program_studi')
                ->pluck('id_unw_program_studi')
                ->map(fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->values()
                ->all();
        } catch (Throwable $exception) {
            report($exception);

            return [];
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $sections
     * @return array<int, array<string, mixed>>
     */
    private function uniqueSections(array $sections): array
    {
        return collect($sections)
            ->filter(fn ($section): bool => is_array($section))
            ->unique(function (array $section): string {
                $detailId = (string) ($section['idtawarmatakuliahdetail'] ?? '');
                $courseId = (string) ($section['idmatakuliah'] ?? '');

                return $detailId !== '' ? 'd:'.$detailId : 'm:'.$courseId;
            })
            ->values()
            ->all();
    }

    private function isMissingProgramStudiColumnError(RequestException $exception): bool
    {
        $message = strtolower($exception->getMessage().' '.$this->requestExceptionBody($exception));

        return str_contains($message, 'ps.id_unw_program_studi')
            || str_contains($message, "unknown column 'ps.id_unw_program_studi'");
    }

    private function requestExceptionBody(RequestException $exception): string
    {
        $response = $exception->response ?? null;

        return $response === null ? '' : (string) $response->body();
    }
}
