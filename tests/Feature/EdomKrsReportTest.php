<?php

namespace Tests\Feature;

use App\Filament\Resources\EdomReports\EdomReportResource;
use App\Models\EdomPeriod;
use App\Models\EdomResponse;
use App\Models\EdomSettings;
use App\Models\ProgramStudi;
use App\Services\Edom\EdomKrsReportData;
use App\Services\Siakad\UnwApiSiakad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class EdomKrsReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_uses_current_krs_metadata_without_a_local_krs_table(): void
    {
        Cache::flush();

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
            'status' => EdomSettings::STATUS_ACTIVE,
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
            ->atLeast()
            ->once()
            ->with('18273', 2025, 1)
            ->andReturn([
                $this->section(22489, 3926, '24KK01', 'Hukum Kesehatan Dan Digital'),
                $this->section(32489, 3926, '24KK01 B', 'Hukum Kesehatan Dan Digital'),
                $this->section(22494, 3931, '24KK02', 'Hukum Pembuktian Tindak Pidana Digital'),
            ]);
        $siakad->shouldReceive('penawaran')
            ->zeroOrMoreTimes()
            ->andReturn([]);
        $this->app->instance(UnwApiSiakad::class, $siakad);

        $result = app(EdomKrsReportData::class)->refreshKnownResponseMetadata();

        $this->assertSame(1, $result['student_periods']);
        $this->assertSame(3, $result['fetched_sections']);
        $this->assertSame(1, $result['updated_responses']);
        $this->assertFalse(Schema::hasTable('edom_krs_sections'));
        $this->assertDatabaseHas('edom_response', [
            'id' => $response->id,
            'id_unw_program_studi' => 22,
        ]);
        $this->assertSame(2, EdomReportResource::courseCountForProgramStudi($programStudi));
        $this->assertSame(1, EdomReportResource::responseCountForProgramStudi($programStudi));
        $this->assertSame(
            [3926, 3931],
            EdomReportResource::coursesForProgramStudi($programStudi)
                ->pluck('siakad_idmatakuliah')
                ->map(fn ($id): int => (int) $id)
                ->sort()
                ->values()
                ->all(),
        );
        $this->assertSame('m_3926', EdomReportResource::courseKeyForCourseId(3926));
    }

    public function test_response_counts_fall_back_to_submitted_program_studi_and_course_data(): void
    {
        Cache::flush();

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
            'status' => EdomSettings::STATUS_ACTIVE,
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

        $reportData = Mockery::mock(EdomKrsReportData::class);
        $reportData->shouldReceive('courseCountForProgramStudi')->andReturn(0);
        $this->app->instance(EdomKrsReportData::class, $reportData);

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

    public function test_refresh_replaces_stale_program_studi_from_latest_krs_api(): void
    {
        Cache::flush();

        $period = EdomPeriod::query()->create([
            'year' => 2025,
            'siakad_idsemester' => 1,
        ]);
        $setting = EdomSettings::query()->create([
            'name' => 'EDOM 2025',
            'status' => EdomSettings::STATUS_ACTIVE,
        ]);
        $response = EdomResponse::query()->create([
            'edom_period_id' => $period->id,
            'edom_setting_id' => $setting->id,
            'siakad_idmahasiswa' => '18273',
            'siakad_idmatakuliah' => 3931,
            'siakad_idtawarmatakuliahdetail' => 22494,
            'id_unw_program_studi' => 99,
            'submitted_at' => now(),
        ]);

        $siakad = Mockery::mock(UnwApiSiakad::class);
        $siakad->shouldReceive('krs')
            ->once()
            ->with('18273', 2025, 1)
            ->andReturn([
                $this->section(22494, 3931, '24KK02', 'Hukum Pembuktian Tindak Pidana Digital'),
            ]);
        $this->app->instance(UnwApiSiakad::class, $siakad);

        $result = app(EdomKrsReportData::class)->refreshKnownResponseMetadata();

        $this->assertSame(1, $result['updated_responses']);
        $this->assertFalse(Schema::hasTable('edom_krs_sections'));
        $this->assertDatabaseHas('edom_response', [
            'id' => $response->id,
            'id_unw_program_studi' => 22,
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
            'dosen_team' => [],
            'id_unw_program_studi' => 22,
        ];
    }
}
