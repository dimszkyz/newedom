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

    public function test_report_counts_responses_and_courses_from_edom_response_program_studi_id(): void
    {
        $programStudi = ProgramStudi::query()->create([
            'id_unw_program_studi' => 22,
            'nama' => 'Magister Hukum',
        ]);
        $otherProgramStudi = ProgramStudi::query()->create([
            'id_unw_program_studi' => 33,
            'nama' => 'Magister Manajemen',
        ]);
        $period = EdomPeriod::query()->create([
            'year' => 2025,
            'siakad_idsemester' => 1,
        ]);
        $setting = EdomSettings::query()->create([
            'name' => 'EDOM 2025',
            'status' => 'active',
        ]);

        EdomResponse::query()->create([
            'edom_period_id' => $period->id,
            'edom_setting_id' => $setting->id,
            'siakad_idmahasiswa' => '18273',
            'siakad_idmatakuliah' => 3926,
            'siakad_idtawarmatakuliahdetail' => 22489,
            'submitted_at' => now(),
        ]);
        EdomResponse::query()->create([
            'edom_period_id' => $period->id,
            'edom_setting_id' => $setting->id,
            'siakad_idmahasiswa' => '18273',
            'siakad_idmatakuliah' => 3931,
            'siakad_idtawarmatakuliahdetail' => 22494,
            'submitted_at' => now(),
        ]);
        EdomResponse::query()->create([
            'edom_period_id' => $period->id,
            'edom_setting_id' => $setting->id,
            'siakad_idmahasiswa' => '18273',
            'siakad_idmatakuliah' => 4099,
            'siakad_idtawarmatakuliahdetail' => 23689,
            'submitted_at' => now(),
        ]);

        $siakad = Mockery::mock(UnwApiSiakad::class);
        $siakad->shouldReceive('krs')
            ->once()
            ->with('18273', 2025, 1)
            ->andReturn([
                $this->section(22489, 3926, '24KK01', 'Hukum Kesehatan Dan Digital', 22),
                $this->section(22494, 3931, '24KK02', 'Hukum Pembuktian Tindak Pidana Digital', 22),
                $this->section(23689, 4099, '24KU14 A', 'Perbuatan Melawan Hukum Korporasi', 33),
            ]);
        $this->app->instance(UnwApiSiakad::class, $siakad);

        $result = app(EdomKrsSectionSyncService::class)->syncKnownStudentPeriods();

        $this->assertSame(1, $result['student_periods']);
        $this->assertSame(3, $result['synced_sections']);
        $this->assertDatabaseCount('edom_krs_sections', 3);
        $this->assertDatabaseHas('edom_response', [
            'siakad_idmatakuliah' => 3926,
            'id_unw_program_studi' => 22,
        ]);
        $this->assertDatabaseHas('edom_response', [
            'siakad_idmatakuliah' => 4099,
            'id_unw_program_studi' => 33,
        ]);
        $this->assertSame(2, EdomReportResource::courseCountForProgramStudi($programStudi));
        $this->assertSame(2, EdomReportResource::responseCountForProgramStudi($programStudi));
        $this->assertSame(1, EdomReportResource::responseCountForProgramStudiAndCourse($programStudi, 'm_3926'));
        $this->assertSame(1, EdomReportResource::courseCountForProgramStudi($otherProgramStudi));
        $this->assertSame(1, EdomReportResource::responseCountForProgramStudi($otherProgramStudi));

        $course = EdomKrsSection::query()->where('idmatakuliah', 3926)->firstOrFail();
        $this->assertSame('m_3926', EdomReportResource::courseKeyForKrsSection($course));
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

    public function test_edom_response_fills_program_studi_id_from_cached_krs_section(): void
    {
        $period = EdomPeriod::query()->create([
            'year' => 2025,
            'siakad_idsemester' => 1,
        ]);
        $setting = EdomSettings::query()->create([
            'name' => 'EDOM 2025',
            'status' => 'active',
        ]);

        app(EdomKrsSectionSyncService::class)->syncStudentSections('18273', 2025, 1, [
            $this->section(22489, 3926, '24KK01', 'Hukum Kesehatan Dan Digital', 22),
        ]);

        $response = EdomResponse::query()->create([
            'edom_period_id' => $period->id,
            'edom_setting_id' => $setting->id,
            'siakad_idmahasiswa' => '18273',
            'siakad_idmatakuliah' => 3926,
            'siakad_idtawarmatakuliahdetail' => 22489,
            'submitted_at' => now(),
        ]);

        $this->assertSame(22, $response->refresh()->id_unw_program_studi);
        $this->assertDatabaseHas('edom_response', [
            'id' => $response->id,
            'id_unw_program_studi' => 22,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function section(int $detailId, int $courseId, string $code, string $name, int $programStudiId = 22): array
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
            'id_unw_program_studi' => $programStudiId,
        ];
    }
}
