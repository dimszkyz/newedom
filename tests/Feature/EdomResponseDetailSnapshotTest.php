<?php

namespace Tests\Feature;

use App\Models\EdomPeriod;
use App\Models\EdomQuestion;
use App\Models\EdomQuestionCategory;
use App\Models\EdomQuestionOption;
use App\Models\EdomResponse;
use App\Models\EdomResponseDetail;
use App\Models\EdomSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EdomResponseDetailSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_answer_snapshot_stays_available_after_question_and_option_are_deleted(): void
    {
        $setting = EdomSettings::query()->create([
            'name' => 'EDOM Aktif',
            'status' => 'active',
        ]);
        $category = EdomQuestionCategory::query()->create([
            'edom_setting_id' => $setting->id,
            'name' => 'Pembelajaran',
        ]);
        $question = EdomQuestion::query()->create([
            'edom_setting_id' => $setting->id,
            'edom_question_category_id' => $category->id,
            'statement' => 'Dosen menjelaskan materi dengan jelas.',
            'question_type' => 'option',
        ]);
        $option = EdomQuestionOption::query()->create([
            'edom_setting_id' => $setting->id,
            'name' => 'Sangat Baik',
            'score' => 5,
        ]);
        $period = EdomPeriod::query()->create([
            'year' => 2026,
            'siakad_idsemester' => 2,
        ]);
        $response = EdomResponse::query()->create([
            'edom_period_id' => $period->id,
            'edom_setting_id' => $setting->id,
            'siakad_idmahasiswa' => '18273',
            'siakad_idmatakuliah' => 123,
            'siakad_idtawarmatakuliahdetail' => 4567,
            'submitted_at' => now(),
        ]);

        $detail = EdomResponseDetail::query()->create([
            'edom_response_id' => $response->id,
            'edom_question_id' => $question->id,
            'edom_option_id' => $option->id,
            'answer_text' => null,
        ]);

        $this->assertSame('Pembelajaran', $detail->category_name_snapshot);
        $this->assertSame('Dosen menjelaskan materi dengan jelas.', $detail->question_statement_snapshot);
        $this->assertSame('Sangat Baik', $detail->option_name_snapshot);
        $this->assertSame(5, $detail->option_score_snapshot);

        $option->delete();
        $question->delete();

        $detail = $detail->fresh();

        $this->assertNotNull($detail);
        $this->assertNull($detail->edom_question_id);
        $this->assertNull($detail->edom_option_id);
        $this->assertSame('Pembelajaran', $detail->category_name_for_display);
        $this->assertSame('Dosen menjelaskan materi dengan jelas.', $detail->question_statement_for_display);
        $this->assertSame('Sangat Baik', $detail->option_name_for_display);
        $this->assertSame(5, $detail->option_score_for_display);
    }
}
