<?php

namespace Tests\Feature;

use App\Filament\Resources\EdomReports\EdomReportResource;
use App\Models\EdomKrsSection;
use App\Models\EdomPeriod;
use App\Models\EdomResponse;
use App\Models\EdomSettings;
use App\Models\ProgramStudi;
use App\Services\Edom\EdomKrsSectionSyncService;
use App\Services\Siakad\UnwApiSiakad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class EdomKrsReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_only_lists_krs_courses_that_have_responses(): void
    {
        $programStudi = ProgramStudi::query()->create([
            'id_unw_program_studi' => 22,
            'nama' => 'Magister Hukum',
        ]);
        $period = EdomPeriod::query()->create([
            'year' => 2025,
            'siakad_idsemester' => 1,
        ]);
        $setting = EdomSettings::query()->create([
            'name' => 'EDOM 2025',
            'status' => 'active',
        ]);
        $response = EdomResponse::query()->create([
            'edom_period_id' => $period->id,
            'edom_setting_id' => $setting->id,
            'siakad_idmahasiswa' => '18273',
            'siakad_idmatakuliah' => 3926,
            'siakad_idtawarmatakuliahdetail' => 22489,
            'submitted_at' => now(),
        ]);

        $siakad = Mockery::mock(UnwApiSiakad::class);
        $siakad->shouldReceive('krs')
            ->once()
            ->with('18273', 2025, 1)
            ->andReturn([
                $this->section(22489, 3926, '24KK01', 'Hukum Kesehatan Dan Digital'),
                $this->section(32489, 3926, '24KK01 B', 'Hukum Kesehatan Dan Digital'),
                $this->section(22494, 3931, '24KK02', 'Hukum Pembuktian Tindak Pidana Digital'),
            ]);
        $this->app->instance(UnwApiSiakad::class, $siakad);

        $result = app(EdomKrsSectionSyncService::class)->syncKnownStudentPeriods();

        $this->assertSame(1, $result['student_periods']);
        $this->assertSame(3, $result['synced_sections']);
        $this->assertDatabaseCount('edom_krs_sections', 3);
        $this->assertDatabaseHas('edom_response', [
            'id' => $response->id,
            'id_unw_program_studi' => 22,
        ]);
        $this->assertSame(1, EdomReportResource::courseCountForProgramStudi($programStudi));
        $this->assertSame(1, EdomReportResource::responseCountForProgramStudi($programStudi));
        $this->assertSame(
            [3926],
            EdomReportResource::coursesForProgramStudi($programStudi)
                ->pluck('idmatakuliah')
                ->map(fn ($id): int => (int) $id)
                ->all(),
        );

        $course = EdomKrsSection::query()->where('idmatakuliah', 3926)->firstOrFail();
        $this->assertSame('m_3926', EdomReportResource::courseKeyForKrsSection($course));
    }

    public function test_response_counts_are_scoped_by_submitted_program_studi_and_course(): void
    {
        $magisterHukum = ProgramStudi::query()->create([
            'id_unw_program_studi' => 22,
            'nama' => 'Hukum',
            'jenjang_nama_singkat' => 'S2',
        ]);
        $keperawatan = ProgramStudi::query()->create([
            'id_unw_program_studi' => 1,
            'nama' => 'Keperawatan',
            'jenjang_nama_singkat' => 'D3',
        ]);
        $period = EdomPeriod::query()->create([
            'year' => 2025,
            'siakad_idsemester' => 1,
        ]);
        $setting = EdomSettings::query()->create([
            'name' => 'EDOM Bersama',
            'status' => 'active',
        ]);

        foreach ([3926, 3931, 3927] as $courseId) {
            EdomResponse::query()->create([
                'edom_period_id' => $period->id,
                'edom_setting_id' => $setting->id,
                'siakad_idmahasiswa' => '18273',
                'siakad_idmatakuliah' => $courseId,
                'siakad_idtawarmatakuliahdetail' => $courseId + 18000,
                'id_unw_program_studi' => 22,
                'submitted_at' => now(),
            ]);
        }

        EdomResponse::query()->create([
            'edom_period_id' => $period->id,
            'edom_setting_id' => $setting->id,
            'siakad_idmahasiswa' => '20001',
            'siakad_idmatakuliah' => 5001,
            'siakad_idtawarmatakuliahdetail' => 25001,
            'id_unw_program_studi' => 1,
            'submitted_at' => now(),
        ]);

        $this->assertSame(3, EdomReportResource::responseCountForProgramStudi($magisterHukum));
        $this->assertSame(1, EdomReportResource::responseCountForProgramStudi($keperawatan));
        $this->assertSame(3, EdomReportResource::courseCountForProgramStudi($magisterHukum));
        $this->assertSame(1, EdomReportResource::courseCountForProgramStudi($keperawatan));
        $this->assertSame(
            1,
            EdomReportResource::responseCountForProgramStudiAndCourse($magisterHukum, 'm_3926'),
        );
        $this->assertSame(
            0,
            EdomReportResource::responseCountForProgramStudiAndCourse($keperawatan, 'm_3926'),
        );
    }

    public function test_sync_replaces_stale_krs_courses_for_the_same_student_period(): void
    {
        $siakad = Mockery::mock(UnwApiSiakad::class);
        $this->app->instance(UnwApiSiakad::class, $siakad);
        $sync = app(EdomKrsSectionSyncService::class);

        $sync->syncStudentSections('18273', 2025, 1, [
            $this->section(22489, 3926, '24KK01', 'Hukum Kesehatan Dan Digital'),
            $this->section(22494, 3931, '24KK02', 'Hukum Pembuktian Tindak Pidana Digital'),
        ]);

        $sync->syncStudentSections('18273', 2025, 1, [
            $this->section(22494, 3931, '24KK02', 'Hukum Pembuktian Tindak Pidana Digital'),
        ]);

        $this->assertDatabaseCount('edom_krs_sections', 1);
        $this->assertDatabaseMissing('edom_krs_sections', [
            'siakad_idmahasiswa' => '18273',
            'idmatakuliah' => 3926,
        ]);
        $this->assertDatabaseHas('edom_krs_sections', [
            'siakad_idmahasiswa' => '18273',
            'siakad_idtahunajaran' => 2025,
            'siakad_idsemester' => 1,
            'idmatakuliah' => 3931,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function section(int $detailId, int $courseId, string $code, string $name): array
    {
        return [
            'idtawarmatakuliahdetail' => $detailId,
            'idmatakuliah' => $courseId,
            'kode' => $code,
            'nama' => $name,
            'dosen' => [
                'nidn' => '0609077101',
                'nama' => 'Dosen Pengampu',
            ],
            'dosen_team' => [
                [
                    'nidn' => '0609077101',
                    'nama' => 'Dosen Pengampu',
                ],
            ],
            'id_unw_program_studi' => 22,
        ];
    }
}
