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
            $externalId = data_get($item, 'id');
            $nama = trim((string) data_get($item, 'nama'));

            if (blank($externalId) || $nama === '') {
                $skipped++;

                continue;
            }

            $programStudi = ProgramStudi::query()
                ->where('id_unw_program_studi', $externalId)
                ->first();

            if ($programStudi) {
                $programStudi->update([
                    'nama' => $nama,
                ]);

                $updated++;

                continue;
            }

            ProgramStudi::query()->create([
                'id_unw_program_studi' => $externalId,
                'nama' => $nama,
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
}
