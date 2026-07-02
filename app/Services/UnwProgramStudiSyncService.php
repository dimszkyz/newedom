<?php

namespace App\Services;

use App\Models\ProgramStudi;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class UnwProgramStudiSyncService
{
    public function sync(): array
    {
        $url = config('services.unw_program_studi.url');
        $verifySsl = filter_var(config('services.unw_program_studi.verify_ssl', true), FILTER_VALIDATE_BOOLEAN);

        if (blank($url)) {
            throw new RuntimeException('UNW_PROGRAM_STUDI_API_URL belum diisi di file .env.');
        }

        $response = Http::acceptJson()
            ->withOptions(['verify' => $verifySsl])
            ->timeout(30)
            ->retry(2, 1000)
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('API Program Studi UNW gagal diakses. Status: '.$response->status());
        }

        $items = data_get($response->json(), 'data');

        if (! is_array($items)) {
            throw new RuntimeException('Format response API Program Studi UNW tidak valid.');
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($items as $item) {
            $externalId = $this->unwProgramStudiId($item);
            $nama = $this->unwProgramStudiName($item);

            if ($externalId === null || $nama === '') {
                $skipped++;
                continue;
            }

            $attributes = [
                'id_unw_program_studi' => $externalId,
                'nama' => $nama,
                'slug' => data_get($item, 'slug'),
                'page_slug' => data_get($item, 'page_slug'),
                'jenjang' => data_get($item, 'jenjang', data_get($item, 'degree_level')),
                'jenjang_nama_singkat' => data_get($item, 'jenjang_nama_singkat', data_get($item, 'degree_short_name')),
                'unw_fakultas_id' => data_get($item, 'unwFakultas.id', data_get($item, 'unw_faculty_id')),
                'unw_fakultas_nama' => data_get($item, 'unwFakultas.nama', data_get($item, 'faculty_name')),
                'unw_fakultas_page_slug' => data_get($item, 'unwFakultas.page_slug', data_get($item, 'faculty_page_slug')),
                'api_created_at' => data_get($item, 'createdAt'),
                'api_updated_at' => data_get($item, 'updatedAt', data_get($item, 'api_updated_at')),
                'synced_at' => now(),
            ];

            $programStudi = ProgramStudi::query()
                ->where('id_unw_program_studi', $externalId)
                ->first();

            if ($programStudi) {
                $programStudi->update($attributes);
                $updated++;
                continue;
            }

            ProgramStudi::query()->create($attributes);
            $created++;
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'total' => count($items),
        ];
    }

    private function unwProgramStudiId(mixed $item): ?int
    {
        $id = data_get(
            $item,
            'unwProgramStudi.id',
            data_get($item, 'unw_program_studi.id', data_get($item, 'id_unw_program_studi', data_get($item, 'id')))
        );

        if (blank($id)) {
            return null;
        }

        return (int) $id;
    }

    private function unwProgramStudiName(mixed $item): string
    {
        return trim((string) data_get(
            $item,
            'unwProgramStudi.nama',
            data_get($item, 'unw_program_studi.nama', data_get($item, 'nama', data_get($item, 'name', '')))
        ));
    }
}
