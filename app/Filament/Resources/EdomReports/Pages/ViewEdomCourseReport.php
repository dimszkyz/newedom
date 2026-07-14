<?php

namespace App\Filament\Resources\EdomReports\Pages;

use App\Filament\Resources\EdomReports\EdomReportResource;
use App\Models\EdomResponse;
use App\Models\EdomResponseDetail;
use App\Models\ProgramStudi;
use App\Services\Edom\EdomResponseMetadata;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ViewEdomCourseReport extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = EdomReportResource::class;

    protected string $view = 'filament-panels::resources.pages.list-records';

    public string $courseKey;

    public function mount(int|string $record, string $courseKey): void
    {
        $this->record = $this->resolveRecord($record);
        $this->courseKey = $courseKey;
    }

    public function getTitle(): string
    {
        return 'Report Detail - '.$this->courseName();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->reportQuery())
            ->columns($this->reportColumns())
            ->defaultGroup(
                Group::make('report_category')
                    ->label('Kategori')
                    ->titlePrefixedWithLabel(false)
            )
            ->defaultSort('id')
            ->groupingSettingsHidden()
            ->recordActions([])
            ->toolbarActions([]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToCourses')
                ->label('Kembali ke Mata Kuliah')
                ->icon('heroicon-o-arrow-left')
                ->url(EdomReportResource::getUrl('courses', ['record' => $this->record])),
            Action::make('backToProgramStudis')
                ->label('Kembali ke Program Studi')
                ->icon('heroicon-o-building-library')
                ->url(EdomReportResource::getUrl('index')),
        ];
    }

    private function reportColumns(): array
    {
        $columns = [
            TextColumn::make('report_statement')
                ->label('Pernyataan')
                ->wrap()
                ->limit(160),
            TextColumn::make('answer_count')
                ->label('Jumlah Jawaban')
                ->badge()
                ->color('info'),
            TextColumn::make('essay_answers')
                ->label('Jawaban Esai')
                ->state(fn (EdomResponseDetail $record): ?string => $this->isEssayRow($record)
                    ? $this->textAnswersFor($record)
                        ->values()
                        ->map(fn (string $answer, int $index): string => ($index + 1).'. '.$answer)
                        ->join(' | ')
                    : null)
                ->wrap()
                ->placeholder('-'),
        ];

        foreach ($this->optionLabels() as $index => $optionLabel) {
            $columns[] = TextColumn::make('option_'.$index)
                ->label($optionLabel)
                ->state(fn (EdomResponseDetail $record): ?string => $this->isEssayRow($record)
                    ? null
                    : $this->percentageLabelFor($record, $optionLabel))
                ->description(fn (EdomResponseDetail $record): ?string => $this->isEssayRow($record)
                    ? null
                    : $this->selectedCountFor($record, $optionLabel).' dipilih')
                ->badge()
                ->color('success');
        }

        return $columns;
    }

    private function reportQuery(): Builder
    {
        $responseIds = $this->responseIds();
        $questionTypeExpression = "COALESCE(edom_response_detail.question_type_snapshot, edom_questions.question_type, CASE WHEN edom_response_detail.answer_text IS NOT NULL THEN 'text' ELSE 'option' END)";

        $query = EdomResponseDetail::query()
            ->leftJoin('edom_questions', 'edom_questions.id', '=', 'edom_response_detail.edom_question_id')
            ->leftJoin('edom_question_categories', 'edom_question_categories.id', '=', 'edom_questions.edom_question_category_id')
            ->leftJoin('edom_question_options', 'edom_question_options.id', '=', 'edom_response_detail.edom_option_id')
            ->selectRaw('MIN(edom_response_detail.id) as id')
            ->selectRaw("COALESCE(edom_response_detail.category_name_snapshot, edom_question_categories.name, 'Kategori dihapus') as report_category")
            ->selectRaw("COALESCE(edom_response_detail.question_statement_snapshot, edom_questions.statement, 'Pertanyaan dihapus') as report_statement")
            ->selectRaw($questionTypeExpression.' as report_question_type')
            ->selectRaw('COUNT(edom_response_detail.id) as answer_count')
            ->selectRaw('COUNT(COALESCE(edom_response_detail.option_name_snapshot, edom_question_options.name)) as option_answer_count')
            ->groupBy([
                'edom_response_detail.category_name_snapshot',
                'edom_question_categories.name',
                'edom_response_detail.question_statement_snapshot',
                'edom_questions.statement',
            ])
            ->groupByRaw($questionTypeExpression);

        if ($responseIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('edom_response_detail.edom_response_id', $responseIds->all());
    }

    private function optionLabels(): Collection
    {
        $responseIds = $this->responseIds();

        if ($responseIds->isEmpty()) {
            return collect();
        }

        return EdomResponseDetail::query()
            ->leftJoin('edom_question_options', 'edom_question_options.id', '=', 'edom_response_detail.edom_option_id')
            ->whereIn('edom_response_detail.edom_response_id', $responseIds->all())
            ->selectRaw('COALESCE(edom_response_detail.option_name_snapshot, edom_question_options.name) as option_label')
            ->selectRaw('COALESCE(edom_response_detail.option_score_snapshot, edom_question_options.score, 999) as option_score')
            ->whereRaw('COALESCE(edom_response_detail.option_name_snapshot, edom_question_options.name) IS NOT NULL')
            ->orderBy('option_score')
            ->get()
            ->pluck('option_label')
            ->filter()
            ->unique()
            ->values();
    }

    private function percentageLabelFor(EdomResponseDetail $record, string $optionLabel): string
    {
        $total = $this->optionAnswerCountFor($record);

        if ($total <= 0) {
            return '0%';
        }

        $percentage = ($this->selectedCountFor($record, $optionLabel) / $total) * 100;

        if ($percentage === round($percentage)) {
            return number_format($percentage, 0, ',', '.').'%';
        }

        return number_format($percentage, 2, ',', '.').'%';
    }

    private function selectedCountFor(EdomResponseDetail $record, string $optionLabel): int
    {
        return (clone $this->detailsForReportRow($record))
            ->whereRaw('COALESCE(edom_response_detail.option_name_snapshot, edom_question_options.name) = ?', [$optionLabel])
            ->count();
    }

    private function optionAnswerCountFor(EdomResponseDetail $record): int
    {
        return (clone $this->detailsForReportRow($record))
            ->whereRaw('COALESCE(edom_response_detail.option_name_snapshot, edom_question_options.name) IS NOT NULL')
            ->count();
    }

    private function detailsForReportRow(EdomResponseDetail $record): Builder
    {
        $questionTypeExpression = "COALESCE(edom_response_detail.question_type_snapshot, edom_questions.question_type, CASE WHEN edom_response_detail.answer_text IS NOT NULL THEN 'text' ELSE 'option' END)";

        return EdomResponseDetail::query()
            ->leftJoin('edom_questions', 'edom_questions.id', '=', 'edom_response_detail.edom_question_id')
            ->leftJoin('edom_question_categories', 'edom_question_categories.id', '=', 'edom_questions.edom_question_category_id')
            ->leftJoin('edom_question_options', 'edom_question_options.id', '=', 'edom_response_detail.edom_option_id')
            ->whereIn('edom_response_detail.edom_response_id', $this->responseIds()->all())
            ->whereRaw("COALESCE(edom_response_detail.category_name_snapshot, edom_question_categories.name, 'Kategori dihapus') = ?", [$record->getAttribute('report_category')])
            ->whereRaw("COALESCE(edom_response_detail.question_statement_snapshot, edom_questions.statement, 'Pertanyaan dihapus') = ?", [$record->getAttribute('report_statement')])
            ->whereRaw($questionTypeExpression.' = ?', [$record->getAttribute('report_question_type')]);
    }

    private function textAnswersFor(EdomResponseDetail $record): Collection
    {
        return (clone $this->detailsForReportRow($record))
            ->whereNotNull('edom_response_detail.answer_text')
            ->where('edom_response_detail.answer_text', '<>', '')
            ->pluck('edom_response_detail.answer_text')
            ->map(fn ($answer): string => trim((string) $answer))
            ->filter()
            ->unique()
            ->values();
    }

    private function isEssayRow(EdomResponseDetail $record): bool
    {
        return in_array(
            strtolower(trim((string) $record->getAttribute('report_question_type'))),
            ['text', 'essay', 'esai'],
            true,
        );
    }

    private function responseIds(): Collection
    {
        return $this->responsesForCourse()->pluck('id')->map(fn ($id): int => (int) $id)->values();
    }

    private function responsesForCourse(): Collection
    {
        /** @var ProgramStudi $programStudi */
        $programStudi = $this->record;

        $query = EdomReportResource::responsesForProgramStudi($programStudi)
            ->with(['period', 'edomSettings']);

        if (str_starts_with($this->courseKey, 'd_')) {
            $query->where('siakad_idtawarmatakuliahdetail', (int) substr($this->courseKey, 2));
        } elseif (str_starts_with($this->courseKey, 'm_')) {
            $query->where('siakad_idmatakuliah', (int) substr($this->courseKey, 2));
        } else {
            return collect();
        }

        return $query->get();
    }

    private function courseName(): string
    {
        $response = $this->responsesForCourse()->first();

        return $response ? app(EdomResponseMetadata::class)->courseNameFor($response) : 'Mata Kuliah';
    }
}
