<?php

namespace App\Services\Edom;

use App\Models\EdomKrsSection;
use App\Models\EdomResponse;
use App\Services\Siakad\UnwApiSiakad;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class EdomKrsSectionSyncService
{
    public function __construct(private readonly UnwApiSiakad $siakad) {}

    /**
     * Mengambil KRS dari API /edom/krs untuk mahasiswa/periode yang sudah pernah submit EDOM,
     * lalu menyimpan section KRS ke tabel cache lokal agar laporan Filament bisa memakai query bawaan.
     *
     * @return array{student_periods:int, synced_sections:int}
     */
    public function syncKnownStudentPeriods(): array
    {
        $studentPeriods = EdomResponse::query()
            ->join('edom_periods', 'edom_periods.id', '=', 'edom_response.edom_period_id')
            ->select([
                'edom_response.siakad_idmahasiswa',
                'edom_periods.year as siakad_idtahunajaran',
                'edom_periods.siakad_idsemester',
            ])
            ->distinct()
            ->get();

        $syncedSections = 0;

        foreach ($studentPeriods as $studentPeriod) {
            $syncedSections += $this->syncStudentPeriod(
                (string) $studentPeriod->siakad_idmahasiswa,
                (int) $studentPeriod->siakad_idtahunajaran,
                (int) $studentPeriod->siakad_idsemester,
            );
        }

        return [
            'student_periods' => $studentPeriods->count(),
            'synced_sections' => $syncedSections,
        ];
    }

    public function syncStudentSections(string $studentId, int $tahunAjaran, int $semester, array $sections): int
    {
        $validSections = collect($sections)
            ->filter(fn (mixed $section): bool => is_array($section)
                && filled(data_get($section, 'idmatakuliah')))
            ->values();

        DB::transaction(function () use ($studentId, $tahunAjaran, $semester, $validSections): void {
            EdomKrsSection::query()
                ->where('siakad_idmahasiswa', $studentId)
                ->where('siakad_idtahunajaran', $tahunAjaran)
                ->where('siakad_idsemester', $semester)
                ->delete();

            foreach ($validSections as $section) {
                $detailId = data_get($section, 'idtawarmatakuliahdetail');
                $courseId = data_get($section, 'idmatakuliah');
                $lecturer = data_get($section, 'dosen', []);

                EdomKrsSection::query()->create([
                    'siakad_idmahasiswa' => $studentId,
                    'siakad_idtahunajaran' => $tahunAjaran,
                    'siakad_idsemester' => $semester,
                    'idtawarmatakuliahdetail' => blank($detailId) ? null : (int) $detailId,
                    'idmatakuliah' => (int) $courseId,
                    'kode' => filled(data_get($section, 'kode')) ? (string) data_get($section, 'kode') : null,
                    'nama' => (string) data_get($section, 'nama', 'Mata kuliah #'.$courseId),
                    'dosen_nidn' => is_array($lecturer) && filled(data_get($lecturer, 'nidn')) ? (string) data_get($lecturer, 'nidn') : null,
                    'dosen_nama' => is_array($lecturer) && filled(data_get($lecturer, 'nama')) ? (string) data_get($lecturer, 'nama') : null,
                    'dosen_team' => data_get($section, 'dosen_team'),
                    'id_unw_program_studi' => filled(data_get($section, 'id_unw_program_studi')) ? (int) data_get($section, 'id_unw_program_studi') : null,
                    'fetched_at' => now(),
                ]);
            }
        });

        $this->backfillResponseProgramStudiIds($studentId, $tahunAjaran, $semester);

        return $validSections->count();
    }

    private function syncStudentPeriod(string $studentId, int $tahunAjaran, int $semester): int
    {
        try {
            $sections = Cache::remember(
                'edom-report:krs:'.$studentId.':'.$tahunAjaran.':'.$semester,
                now()->addMinutes(30),
                fn (): array => $this->siakad->krs($studentId, $tahunAjaran, $semester),
            );
        } catch (Throwable $exception) {
            report($exception);

            return 0;
        }

        return $this->syncStudentSections($studentId, $tahunAjaran, $semester, $sections);
    }

    private function backfillResponseProgramStudiIds(string $studentId, int $tahunAjaran, int $semester): void
    {
        $periodIds = DB::table('edom_periods')
            ->where('year', $tahunAjaran)
            ->where('siakad_idsemester', $semester)
            ->pluck('id');

        if ($periodIds->isEmpty()) {
            return;
        }

        EdomKrsSection::query()
            ->where('siakad_idmahasiswa', $studentId)
            ->where('siakad_idtahunajaran', $tahunAjaran)
            ->where('siakad_idsemester', $semester)
            ->whereNotNull('id_unw_program_studi')
            ->get(['idmatakuliah', 'id_unw_program_studi'])
            ->unique(fn (EdomKrsSection $section): string => (string) $section->idmatakuliah)
            ->each(function (EdomKrsSection $section) use ($studentId, $periodIds): void {
                EdomResponse::query()
                    ->whereIn('edom_period_id', $periodIds->all())
                    ->where('siakad_idmahasiswa', $studentId)
                    ->where('siakad_idmatakuliah', (int) $section->idmatakuliah)
                    ->whereNull('id_unw_program_studi')
                    ->update(['id_unw_program_studi' => (int) $section->id_unw_program_studi]);
            });
    }
}
