<?php

namespace App\Services\Siakad;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

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
                throw new InvalidArgumentException('UNW_API_SIAKAD_EMAIL and UNW_API_SIAKAD_PASSWORD must be configured.');
            }

            $response = Http::asJson()
                ->acceptJson()
                ->post($this->baseUrl().'/login', [
                    'email' => $email,
                    'password' => $password,
                ])
                ->throw();

            $token = $response->json('data.token');

            if (! is_string($token) || $token === '') {
                throw new InvalidArgumentException('unw-api-siakad login response does not contain data.token.');
            }

            return $token;
        });
    }

    private function client(): PendingRequest
    {
        return Http::withToken($this->token())
            ->acceptJson()
            ->baseUrl($this->baseUrl());
    }

    private function baseUrl(): string
    {
        $baseUrl = rtrim((string) config('services.unwapi_siakad.base'), '/');

        if ($baseUrl === '') {
            throw new InvalidArgumentException('UNW_API_SIAKAD_BASE_URL must be configured.');
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

    public function krs(int|string $siakadIdMahasiswa, int|string $siakadIdTahunAjaran, int|string $siakadIdSemester): array
    {
        return $this->request('get', '/edom/krs', [
            'siakad_idmahasiswa' => $siakadIdMahasiswa,
            'siakad_idtahunajaran' => $siakadIdTahunAjaran,
            'siakad_idsemester' => $siakadIdSemester,
        ]);
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
            'siakad_idmahasiswa' => $siakadIdMahasiswa,
            'siakad_idtahunajaran' => $siakadIdTahunAjaran,
            'siakad_idsemester' => $siakadIdSemester,
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
        $query = collect($siakadIdMahasiswa)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => 'siakad_idmahasiswa[]='.rawurlencode((string) $id))
            ->implode('&');

        return $this->request('get', '/edom/mahasiswa'.($query === '' ? '' : '?'.$query));
    }
}
