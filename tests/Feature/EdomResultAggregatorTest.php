<?php

namespace Tests\Feature;

use App\Models\EdomPeriod;
use App\Models\EdomQuestion;
use App\Models\EdomQuestionCategory;
use App\Models\EdomQuestionOption;
use App\Models\EdomResponse;
use App\Models\EdomResponseDetail;
use App\Models\EdomSettings;
use App\Services\Edom\EdomResultAggregator;
use App\Services\Siakad\UnwApiSiakad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class EdomResultAggregatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_aggregates_result_rows_by_offering_category_and_question(): void
    {
        $period = EdomPeriod::query()->create(['year' => 2026, 'siakad_idsemester' => 2]);
        $setting = EdomSettings::query()->create(['name' => 'EDOM Aktif', 'status' => 'active']);
        $category = EdomQuestionCategory::query()->create(['edom_setting_id' => $setting->id, 'name' => 'Pembelajaran']);
        $question = EdomQuestion::query()->create([
            'edom_setting_id' => $setting->id,
            'edom_question_category_id' => $category->id,
            'statement' => 'Dosen menyampaikan materi dengan jelas.',
            'question_type' => 'option',
        ]);
        $scoreFive = EdomQuestionOption::query()->create(['edom_setting_id' => $setting->id, 'name' => 'Sangat Baik', 'score' => 5]);
        $scoreThree = EdomQuestionOption::query()->create(['edom_setting_id' => $setting->id, 'name' => 'Cukup', 'score' => 3]);

        foreach (['1001' => $scoreFive->id, '1002' => $scoreThree->id] as $studentCode => $optionId) {
            $response = EdomResponse::query()->create([
                'edom_period_id' => $period->id,
                'edom_setting_id' => $setting->id,
                'siakad_idmahasiswa' => $studentCode,
                'siakad_idmatakuliah' => 123,
                'siakad_idtawarmatakuliahdetail' => 4567,
                'submitted_at' => now(),
            ]);

            EdomResponseDetail::query()->create([
                'edom_response_id' => $response->id,
                'edom_question_id' => $question->id,
                'edom_option_id' => $optionId,
            ]);
        }

        $siakad = Mockery::mock(UnwApiSiakad::class);
        $siakad->shouldReceive('penawaran')->once()->with(2026, 2, null)->andReturn([
            [
                'idtawarmatakuliahdetail' => 4567,
                'idmatakuliah' => 123,
                'kode' => 'TIF101',
                'nama' => 'Algoritma',
                'dosen' => ['nama' => 'Dosen Testing'],
                'dosen_team' => ['Dosen Pendamping'],
                'id_unw_program_studi' => 14,
            ],
        ]);
        $this->app->instance(UnwApiSiakad::class, $siakad);

        $row = app(EdomResultAggregator::class)->summaries()->first();

        $this->assertSame('TIF101', $row['kode']);
        $this->assertSame('Algoritma', $row['mata_kuliah']);
        $this->assertSame('Dosen Testing', $row['dosen']);
        $this->assertSame('Pembelajaran', $row['category_name']);
        $this->assertSame(2, $row['respondent_count']);
        $this->assertSame(4.0, $row['average_score']);
        $this->assertArrayNotHasKey('siakad_idmahasiswa', $row);
    }
}
