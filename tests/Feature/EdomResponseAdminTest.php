<?php

namespace Tests\Feature;

use App\Filament\Resources\EdomResponses\Pages\ViewStudentEdomResponses;
use App\Filament\Resources\EdomResponses\Tables\EdomResponsesTable;
use App\Models\EdomKrsSection;
use App\Models\EdomPeriod;
use App\Models\EdomQuestion;
use App\Models\EdomQuestionCategory;
use App\Models\EdomQuestionOption;
use App\Models\EdomResponse;
use App\Models\EdomResponseDetail;
use App\Models\EdomSettings;
use App\Services\Edom\EdomResponseMetadata;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EdomResponseAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_response_list_groups_courses_filled_by_the_same_student(): void
    {
        [$period, $setting] = $this->createResponseDependencies();
        $first = $this->createResponse($period, $setting, 3926, 22489, now()->subMinute());
        $this->createResponse($period, $setting, 3931, 22494, now());
        $this->cacheCourse($first, '24KK01', 'Hukum Kesehatan Dan Digital');
        $this->cacheCourse(
            EdomResponse::query()->where('siakad_idmatakuliah', 3931)->firstOrFail(),
            '24KK02',
            'Hukum Pembuktian Tindak Pidana Digital'
        );

        $groupedResponses = EdomResponsesTable::applyStudentGrouping(EdomResponse::query())->get();

        $this->assertCount(1, $groupedResponses);
        $group = $groupedResponses->firstOrFail();
        $this->assertSame('18273', $group->siakad_idmahasiswa);
        $this->assertSame(2, (int) $group->filled_course_count);
        $this->assertSame([
            '24KK01 - Hukum Kesehatan Dan Digital',
            '24KK02 - Hukum Pembuktian Tindak Pidana Digital',
        ], app(EdomResponseMetadata::class)->courseLabelsForStudentGroup($group));
    }

    public function test_student_detail_query_contains_answers_from_all_filled_courses(): void
    {
        [$period, $setting, $question, $option] = $this->createResponseDependencies();
        $first = $this->createResponse($period, $setting, 3926, 22489, now()->subMinute());
        $second = $this->createResponse($period, $setting, 3931, 22494, now());

        foreach ([$first, $second] as $response) {
            EdomResponseDetail::query()->create([
                'edom_response_id' => $response->id,
                'edom_question_id' => $question->id,
                'edom_option_id' => $option->id,
            ]);
        }

        $details = ViewStudentEdomResponses::detailsForStudentGroup(
            '18273',
            $period->id,
            $setting->id,
        )->get();

        $this->assertCount(2, $details);
        $this->assertEqualsCanonicalizing(
            [$first->id, $second->id],
            $details->pluck('edom_response_id')->all()
        );
    }

    /**
     * @return array{EdomPeriod, EdomSettings, EdomQuestion, EdomQuestionOption}
     */
    private function createResponseDependencies(): array
    {
        $period = EdomPeriod::query()->create([
            'year' => 2025,
            'siakad_idsemester' => 1,
        ]);
        $setting = EdomSettings::query()->create([
            'name' => 'EDOM 2025',
            'status' => 'active',
        ]);
        $category = EdomQuestionCategory::query()->create([
            'edom_setting_id' => $setting->id,
            'name' => 'Pembelajaran',
        ]);
        $question = EdomQuestion::query()->create([
            'edom_setting_id' => $setting->id,
            'edom_question_category_id' => $category->id,
            'statement' => 'Dosen menyampaikan materi dengan jelas.',
            'question_type' => 'option',
        ]);
        $option = EdomQuestionOption::query()->create([
            'edom_setting_id' => $setting->id,
            'name' => 'Sangat Baik',
            'score' => 5,
        ]);

        return [$period, $setting, $question, $option];
    }

    private function createResponse(
        EdomPeriod $period,
        EdomSettings $setting,
        int $courseId,
        int $sectionId,
        mixed $submittedAt,
    ): EdomResponse {
        return EdomResponse::query()->create([
            'edom_period_id' => $period->id,
            'edom_setting_id' => $setting->id,
            'siakad_idmahasiswa' => '18273',
            'siakad_idmatakuliah' => $courseId,
            'siakad_idtawarmatakuliahdetail' => $sectionId,
            'submitted_at' => $submittedAt,
        ]);
    }

    private function cacheCourse(EdomResponse $response, string $code, string $name): void
    {
        EdomKrsSection::query()->create([
            'siakad_idmahasiswa' => $response->siakad_idmahasiswa,
            'siakad_idtahunajaran' => $response->period->year,
            'siakad_idsemester' => $response->period->siakad_idsemester,
            'idtawarmatakuliahdetail' => $response->siakad_idtawarmatakuliahdetail,
            'idmatakuliah' => $response->siakad_idmatakuliah,
            'kode' => $code,
            'nama' => $name,
            'id_unw_program_studi' => 22,
            'fetched_at' => now(),
        ]);
    }
}
