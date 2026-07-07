<?php

namespace Tests\Feature;

use App\Filament\Resources\EdomResponses\Pages\ViewStudentEdomResponses;
use App\Filament\Resources\EdomResponses\Tables\EdomResponsesTable;
use App\Models\EdomPeriod;
use App\Models\EdomQuestion;
use App\Models\EdomQuestionCategory;
use App\Models\EdomQuestionOption;
use App\Models\EdomResponse;
use App\Models\EdomResponseDetail;
use App\Models\EdomSettings;
use App\Services\Edom\EdomResponseMetadata;
use App\Services\Siakad\UnwApiSiakad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class EdomResponseAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_response_list_groups_courses_filled_by_the_same_student(): void
    {
        Cache::flush();

        [$period, $setting] = $this->createResponseDependencies();
        $this->createResponse($period, $setting, 3926, 22489, now()->subMinute());
        $this->createResponse($period, $setting, 3931, 22494, now());

        $siakad = Mockery::mock(UnwApiSiakad::class);
        $siakad->shouldReceive('krs')
            ->once()
            ->with('18273', 2025, 1)
            ->andReturn([
                $this->section(22489, 3926, '24KK01', 'Hukum Kesehatan Dan Digital'),
                $this->section(22494, 3931, '24KK02', 'Hukum Pembuktian Tindak Pidana Digital'),
            ]);
        $this->app->instance(UnwApiSiakad::class, $siakad);

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

    public function test_student_detail_query_lists_all_filled_courses_before_their_answers(): void
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

        $responses = ViewStudentEdomResponses::responsesForStudentGroup(
            '18273',
            $period->id,
            $setting->id,
        )->get();

        $this->assertCount(2, $responses);
        $this->assertEqualsCanonicalizing(
            [$first->id, $second->id],
            $responses->pluck('id')->all()
        );
        $this->assertSame([1, 1], $responses->pluck('details_count')->all());
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
            'id_unw_program_studi' => 22,
        ];
    }
}
