<?php

namespace App\Filament\Pages;

use App\Models\EdomResponse;
use App\Models\EdomResponseDetail;
use App\Models\ProgramStudi;
use App\Services\Edom\EdomResponseMetadata;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EdomReports extends Page
{
    protected static ?string $navigationLabel = 'EDOM Reports';

    protected static string|\UnitEnum|null $navigationGroup = 'EDOM';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.pages.edom-reports';

    public function getTitle(): string
    {
        return 'EDOM Reports';
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function programStudiRows(): Collection
    {
        return ProgramStudi::query()
            ->orderBy('jenjang_nama_singkat')
            ->orderBy('nama')
            ->get()
            ->map(function (ProgramStudi $programStudi): array {
                $settingIds = $this->settingIdsForProgramStudi($programStudi);

                $courseCount = $settingIds->isEmpty()
                    ? 0
                    : EdomResponse::query()
                        ->whereIn('edom_setting_id', $settingIds)
                        ->selectRaw("COUNT(DISTINCT CONCAT(siakad_idmatakuliah, '-', siakad_idtawarmatakuliahdetail)) as aggregate")
                        ->value('aggregate');

                $responseCount = $settingIds->isEmpty()
                    ? 0
                    : EdomResponse::query()
                        ->whereIn('edom_setting_id', $settingIds)
                        ->count();

                return [
                    'id' => $programStudi->id,
                    'label' => $programStudi->display_name,
                    'faculty' => $programStudi->nama_fakultas ?: '-',
                    'course_count' => (int) $courseCount,
                    'response_count' => (int) $responseCount,
                    'url' => $this->reportUrl(['program_studi_id' => $programStudi->id]),
                ];
            })
            ->values();
    }

    public function selectedProgramStudi(): ?ProgramStudi
    {
        $programStudiId = request()->integer('program_studi_id');

        if ($programStudiId <= 0) {
            return null;
        }

        return ProgramStudi::query()->find($programStudiId);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function courseRows(): Collection
    {
        $programStudi = $this->selectedProgramStudi();

        if (! $programStudi) {
            return collect();
        }

        $settingIds = $this->settingIdsForProgramStudi($programStudi);

        if ($settingIds->isEmpty()) {
            return collect();
        }

        $metadata = app(EdomResponseMetadata::class);

        return EdomResponse::query()
            ->whereIn('edom_setting_id', $settingIds)
            ->with(['period', 'edomSettings'])
            ->latest('submitted_at')
            ->get()
            ->groupBy(fn (EdomResponse $response): string => $this->courseKeyForResponse($response))
            ->map(function (Collection $responses, string $courseKey) use ($metadata, $programStudi): array {
                /** @var EdomResponse $firstResponse */
                $firstResponse = $responses->first();

                return [
                    'key' => $courseKey,
                    'course_name' => $metadata->courseNameFor($firstResponse),
                    'course_label' => $metadata->courseLabelFor($firstResponse),
                    'course_id' => $firstResponse->siakad_idmatakuliah,
                    'section_id' => $firstResponse->siakad_idtawarmatakuliahdetail,
                    'response_count' => $responses->count(),
                    'respondent_count' => $responses->pluck('siakad_idmahasiswa')->unique()->count(),
                    'url' => $this->reportUrl([
                        'program_studi_id' => $programStudi->id,
                        'course_key' => $courseKey,
                    ]),
                ];
            })
            ->sortBy('course_name')
            ->values();
    }

    public function selectedCourseRow(): ?array
    {
        $courseKey = request()->query('course_key');

        if (! is_string($courseKey) || trim($courseKey) === '') {
            return null;
        }

        return $this->courseRows()
            ->first(fn (array $row): bool => $row['key'] === $courseKey);
    }

    /**
     * @return array{option_labels: Collection<int, string>, categories: Collection<int, array<string, mixed>>, response_count: int, respondent_count: int}
     */
    public function courseReport(): array
    {
        $programStudi = $this->selectedProgramStudi();
        $courseKey = request()->query('course_key');

        if (! $programStudi || ! is_string($courseKey) || trim($courseKey) === '') {
            return [
                'option_labels' => collect(),
                'categories' => collect(),
                'response_count' => 0,
                'respondent_count' => 0,
            ];
        }

        $responses = $this->responsesForProgramStudiAndCourse($programStudi, $courseKey);
        $responseIds = $responses->pluck('id');

        if ($responseIds->isEmpty()) {
            return [
                'option_labels' => collect(),
                'categories' => collect(),
                'response_count' => 0,
                'respondent_count' => 0,
            ];
        }

        $details = EdomResponseDetail::query()
            ->whereIn('edom_response_id', $responseIds)
            ->with(['question.category', 'questionOption'])
            ->orderBy('id')
            ->get();

        $optionLabels = $details
            ->filter(fn (EdomResponseDetail $detail): bool => filled($detail->option_name_for_display))
            ->sortBy(fn (EdomResponseDetail $detail): int => $detail->option_score_for_display ?? 999)
            ->map(fn (EdomResponseDetail $detail): string => (string) $detail->option_name_for_display)
            ->unique()
            ->values();

        $categories = $details
            ->groupBy(fn (EdomResponseDetail $detail): string => $detail->category_name_for_display)
            ->map(function (Collection $categoryDetails, string $categoryName) use ($optionLabels): array {
                $questions = $categoryDetails
                    ->groupBy(fn (EdomResponseDetail $detail): string => $this->questionGroupKey($detail))
                    ->map(function (Collection $questionDetails) use ($optionLabels): array {
                        /** @var EdomResponseDetail $firstDetail */
                        $firstDetail = $questionDetails->first();
                        $optionAnswerCount = $questionDetails
                            ->filter(fn (EdomResponseDetail $detail): bool => filled($detail->option_name_for_display))
                            ->count();

                        $options = $optionLabels->map(function (string $optionLabel) use ($questionDetails, $optionAnswerCount): array {
                            $selectedCount = $questionDetails
                                ->filter(fn (EdomResponseDetail $detail): bool => $detail->option_name_for_display === $optionLabel)
                                ->count();

                            $percentage = $optionAnswerCount > 0
                                ? round(($selectedCount / $optionAnswerCount) * 100, 2)
                                : 0;

                            return [
                                'label' => $optionLabel,
                                'selected_count' => $selectedCount,
                                'percentage' => $percentage,
                                'percentage_label' => $this->formatPercentage($percentage),
                            ];
                        });

                        return [
                            'statement' => $firstDetail->question_statement_for_display,
                            'answer_count' => $questionDetails->count(),
                            'option_answer_count' => $optionAnswerCount,
                            'options' => $options,
                        ];
                    })
                    ->values();

                return [
                    'name' => $categoryName,
                    'questions' => $questions,
                ];
            })
            ->values();

        return [
            'option_labels' => $optionLabels,
            'categories' => $categories,
            'response_count' => $responses->count(),
            'respondent_count' => $responses->pluck('siakad_idmahasiswa')->unique()->count(),
        ];
    }

    public function reportUrl(array $parameters = []): string
    {
        $path = request()->path();
        $query = http_build_query($parameters);

        return url($path).($query !== '' ? '?'.$query : '');
    }

    /**
     * @return Collection<int, int>
     */
    private function settingIdsForProgramStudi(ProgramStudi $programStudi): Collection
    {
        return DB::table('edom_settings_program_studi')
            ->where('program_studi_id', $programStudi->id)
            ->pluck('edom_setting_id')
            ->map(fn ($id): int => (int) $id)
            ->values();
    }

    private function courseKeyForResponse(EdomResponse $response): string
    {
        $sectionId = (int) $response->siakad_idtawarmatakuliahdetail;

        if ($sectionId > 0) {
            return 'd_'.$sectionId;
        }

        return 'm_'.((int) $response->siakad_idmatakuliah);
    }

    /**
     * @return Collection<int, EdomResponse>
     */
    private function responsesForProgramStudiAndCourse(ProgramStudi $programStudi, string $courseKey): Collection
    {
        $settingIds = $this->settingIdsForProgramStudi($programStudi);

        if ($settingIds->isEmpty()) {
            return collect();
        }

        $query = EdomResponse::query()
            ->whereIn('edom_setting_id', $settingIds)
            ->with(['period', 'edomSettings']);

        if (str_starts_with($courseKey, 'd_')) {
            $query->where('siakad_idtawarmatakuliahdetail', (int) substr($courseKey, 2));
        } elseif (str_starts_with($courseKey, 'm_')) {
            $query->where('siakad_idmatakuliah', (int) substr($courseKey, 2));
        } else {
            return collect();
        }

        return $query->get();
    }

    private function questionGroupKey(EdomResponseDetail $detail): string
    {
        $questionId = $detail->edom_question_id;
        $statement = $detail->question_statement_for_display;

        return ($questionId ? 'q_'.$questionId : 'deleted').'_'.md5($statement);
    }

    private function formatPercentage(float $percentage): string
    {
        if ($percentage === round($percentage)) {
            return number_format($percentage, 0, ',', '.').'%';
        }

        return number_format($percentage, 2, ',', '.').'%';
    }
}
