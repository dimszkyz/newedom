<?php

namespace Tests\Feature;

use App\Models\EdomPeriod;
use App\Models\EdomResponse;
use App\Models\EdomResponseDetail;
use App\Models\EdomSettings;
use App\Models\ProgramStudi;
use App\Services\Edom\EdomResponseMetadata;
use App\Services\Edom\EdomResponsesExcelExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class EdomResponsesExcelExporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exports_response_summary_and_option_and_essay_details(): void
    {
        $programStudi = ProgramStudi::query()->create([
            'id_unw_program_studi' => 14,
            'nama' => 'Teknik Informatika',
            'jenjang_nama_singkat' => 'S1',
        ]);
        $period = EdomPeriod::query()->create([
            'year' => 2026,
            'siakad_idsemester' => 1,
        ]);
        $setting = EdomSettings::query()->create([
            'name' => 'EDOM 2026',
            'status' => EdomSettings::STATUS_ACTIVE,
        ]);
        $response = EdomResponse::query()->create([
            'edom_period_id' => $period->id,
            'edom_setting_id' => $setting->id,
            'siakad_idmahasiswa' => '241242001',
            'siakad_idmatakuliah' => 123,
            'siakad_idtawarmatakuliahdetail' => 4567,
            'id_unw_program_studi' => $programStudi->id_unw_program_studi,
            'submitted_at' => now(),
        ]);

        EdomResponseDetail::query()->create([
            'edom_response_id' => $response->id,
            'category_name_snapshot' => 'Pembelajaran',
            'question_statement_snapshot' => 'Dosen menjelaskan materi dengan baik.',
            'question_type_snapshot' => 'option',
            'option_name_snapshot' => 'Sangat Baik',
            'option_score_snapshot' => 5,
        ]);
        EdomResponseDetail::query()->create([
            'edom_response_id' => $response->id,
            'category_name_snapshot' => 'Saran dan Masukan',
            'question_statement_snapshot' => 'Tuliskan saran untuk dosen.',
            'question_type_snapshot' => 'text',
            'answer_text' => 'Pertahankan penjelasan yang sistematis.',
        ]);

        $metadata = Mockery::mock(EdomResponseMetadata::class);
        $metadata->shouldReceive('studentNameFor')->zeroOrMoreTimes()->andReturn('Abdul Rozaq');
        $metadata->shouldReceive('studentNimFor')->zeroOrMoreTimes()->andReturn('241242001');
        $metadata->shouldReceive('tahunAjaranFor')->zeroOrMoreTimes()->andReturn('2026');
        $metadata->shouldReceive('semesterNameFor')->zeroOrMoreTimes()->andReturn('Semester Gasal');
        $metadata->shouldReceive('krsCourseLabelFor')->zeroOrMoreTimes()->andReturn('TIF101 - Algoritma');
        $this->app->instance(EdomResponseMetadata::class, $metadata);

        $xml = app(EdomResponsesExcelExporter::class)->toXml();

        $this->assertStringContainsString('Ringkasan EDOM Response', $xml);
        $this->assertStringContainsString('Abdul Rozaq', $xml);
        $this->assertStringContainsString('S1 - Teknik Informatika', $xml);
        $this->assertStringContainsString('TIF101 - Algoritma', $xml);
        $this->assertStringContainsString('Sangat Baik', $xml);
        $this->assertStringContainsString('Pertahankan penjelasan yang sistematis.', $xml);
        $this->assertStringContainsString('Jawaban Esai', $xml);
    }
}
