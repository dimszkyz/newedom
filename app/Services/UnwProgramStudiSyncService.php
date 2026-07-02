<?php

namespace App\Services;

use App\Models\ProgramStudi;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

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
            ->withOptions([
                'verify' => $verifySsl,
            ])
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
            $payload = $this->payloadFromApiItem($item);

            if ($payload['id_unw_program_studi'] === null || $payload['nama'] === '') {
                $skipped++;

                continue;
            }

            $programStudi = ProgramStudi::query()
                ->where('id_unw_program_studi', $payload['id_unw_program_studi'])
                ->first();

            if ($programStudi) {
                $programStudi->update($payload);

                $updated++;

                continue;
            }

            ProgramStudi::query()->create($payload);

            $created++;
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'total' => count($items),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadFromApiItem(mixed $item): array
    {
        $faculty = data_get($item, 'unwFakultas', data_get($item, 'unw_fakultas', []));

        return [
            'id_unw_program_studi' => $this->unwProgramStudiId($item),
            'nama' => $this->unwProgramStudiName($item),
            'slug' => $this->nullableString(data_get($item, 'slug')),
            'page_slug' => $this->nullableString(data_get($item, 'page_slug')),
            'jenjang' => $this->nullableString(data_get($item, 'jenjang')),
            'jenjang_nama_singkat' => $this->nullableString(data_get($item, 'jenjang_nama_singkat')),
            'id_unw_fakultas' => $this->nullableInteger(data_get($faculty, 'id')),
            'nama_fakultas' => $this->nullableString(data_get($faculty, 'nama')),
            'page_slug_fakultas' => $this->nullableString(data_get($faculty, 'page_slug')),
            'api_updated_at' => $this->nullableDate(data_get($item, 'updatedAt')),
            'synced_at' => now(),
        ];
    }

    private function unwProgramStudiId(mixed $item): ?int
    {
        $id = data_get(
            $item,
            'unwProgramStudi.id',
            data_get($item, 'unw_program_studi.id', data_get($item, 'id_unw_program_studi', data_get($item, 'id')))
        );

        return $this->nullableInteger($id);
    }

    private function unwProgramStudiName(mixed $item): string
    {
        return trim((string) data_get(
            $item,
            'unwProgramStudi.nama',
            data_get($item, 'unw_program_studi.nama', data_get($item, 'nama', ''))
        ));
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableInteger(mixed $value): ?int
    {
        if (blank($value)) {
            return null;
        }

        return (int) $value;
    }

    private function nullableDate(mixed $value): ?Carbon
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
