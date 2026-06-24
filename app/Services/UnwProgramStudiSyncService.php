<?php

namespace App\Services;

use App\Models\Prodi;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class UnwProgramStudiSyncService
{
    public function sync(): array
    {
        $url = config('services.unw_program_studi.url');
        $verifySsl = filter_var(config('services.unw_program_studi.verify_ssl', true), FILTER_VALIDATE_BOOLEAN);

        $response = Http::acceptJson()
            ->withOptions([
                'verify' => $verifySsl,
            ])
            ->timeout(30)
            ->retry(2, 1000)
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('API Program Studi UNW gagal diakses. Status: ' . $response->status());
        }

        $items = data_get($response->json(), 'data');

        if (! is_array($items)) {
            throw new RuntimeException('Format response API Program Studi UNW tidak valid.');
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($items as $item) {
            $externalId = data_get($item, 'id');
            $name = trim((string) data_get($item, 'nama'));

            if (blank($externalId) || $name === '') {
                $skipped++;
                continue;
            }

            $attributes = [
                'name' => $name,
                'slug' => data_get($item, 'slug'),
                'page_slug' => data_get($item, 'page_slug'),
                'degree_level' => data_get($item, 'jenjang'),
                'degree_short_name' => data_get($item, 'jenjang_nama_singkat'),
                'unw_faculty_id' => data_get($item, 'unwFakultas.id'),
                'faculty_name' => trim((string) data_get($item, 'unwFakultas.nama')),
                'faculty_page_slug' => data_get($item, 'unwFakultas.page_slug'),
                'api_updated_at' => $this->parseDate(data_get($item, 'updatedAt')),
                'synced_at' => now(),
            ];

            $prodi = Prodi::query()
                ->where('unw_study_program_id', $externalId)
                ->first();

            if ($prodi) {
                $prodi->update($attributes);
                $updated++;
                continue;
            }

            Prodi::query()->create([
                'unw_study_program_id' => $externalId,
                ...$attributes,
            ]);

            $created++;
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'total' => count($items),
        ];
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
