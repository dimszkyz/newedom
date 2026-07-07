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
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn (): StreamedResponse => $this->exportExcel()),
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

    public function exportExcel(): StreamedResponse
    {
        $filename = 'edom-report-'
            .$this->safeFilename($this->record->display_name)
            .'-'
            .$this->safeFilename($this->courseName())
            .'-'
            .now()->format('Ymd-His')
            .'.xls';

        return response()->streamDownload(
            function (): void {
                echo $this->excelXml();
            },
            $filename,
            [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            ],
        );
    }

    private function reportColumns(): array
    {
        $columns = [
            TextColumn::make('report_statement')
                ->label('Pernyataan')
                ->wrap()
                ->limit(160),
            TextColumn::make('option_answer_count')
                ->label('Jumlah Jawaban')
                ->badge()
                ->color('info'),
        ];

        foreach ($this->optionLabels() as $index => $optionLabel) {
            $columns[] = TextColumn::make('option_'.$index)
                ->label($optionLabel)
                ->state(fn (EdomResponseDetail $record): string => $this->percentageLabelFor($record, $optionLabel))
                ->description(fn (EdomResponseDetail $record): string => $this->selectedCountFor($record, $optionLabel).' dipilih')
                ->badge()
                ->color('success');
        }

        return $columns;
    }

    private function reportQuery(): Builder
    {
        $responseIds = $this->responseIds();

        $query = EdomResponseDetail::query()
            ->leftJoin('edom_questions', 'edom_questions.id', '=', 'edom_response_detail.edom_question_id')
            ->leftJoin('edom_question_categories', 'edom_question_categories.id', '=', 'edom_questions.edom_question_category_id')
            ->leftJoin('edom_question_options', 'edom_question_options.id', '=', 'edom_response_detail.edom_option_id')
            ->selectRaw('MIN(edom_response_detail.id) as id')
            ->selectRaw("COALESCE(edom_response_detail.category_name_snapshot, edom_question_categories.name, 'Kategori dihapus') as report_category")
            ->selectRaw("COALESCE(edom_response_detail.question_statement_snapshot, edom_questions.statement, 'Pertanyaan dihapus') as report_statement")
            ->selectRaw('COUNT(edom_response_detail.id) as answer_count')
            ->selectRaw('COUNT(COALESCE(edom_response_detail.option_name_snapshot, edom_question_options.name)) as option_answer_count')
            ->groupBy([
                'edom_response_detail.category_name_snapshot',
                'edom_question_categories.name',
                'edom_response_detail.question_statement_snapshot',
                'edom_questions.statement',
            ]);

        if ($responseIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('edom_response_detail.edom_response_id', $responseIds->all());
    }

    /**
     * @return Collection<int, string>
     */
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
        return EdomResponseDetail::query()
            ->leftJoin('edom_questions', 'edom_questions.id', '=', 'edom_response_detail.edom_question_id')
            ->leftJoin('edom_question_categories', 'edom_question_categories.id', '=', 'edom_questions.edom_question_category_id')
            ->leftJoin('edom_question_options', 'edom_question_options.id', '=', 'edom_response_detail.edom_option_id')
            ->whereIn('edom_response_detail.edom_response_id', $this->responseIds()->all())
            ->whereRaw("COALESCE(edom_response_detail.category_name_snapshot, edom_question_categories.name, 'Kategori dihapus') = ?", [$record->getAttribute('report_category')])
            ->whereRaw("COALESCE(edom_response_detail.question_statement_snapshot, edom_questions.statement, 'Pertanyaan dihapus') = ?", [$record->getAttribute('report_statement')]);
    }

    /**
     * @return Collection<int, int>
     */
    private function responseIds(): Collection
    {
        return $this->responsesForCourse()->pluck('id')->map(fn ($id): int => (int) $id)->values();
    }

    /**
     * @return Collection<int, EdomResponse>
     */
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

    private function excelXml(): string
    {
        $optionLabels = $this->optionLabels();
        $rows = $this->reportRows();
        $responses = $this->responsesForCourse();
        $exportedAt = now()->format('d/m/Y H:i:s');
        $columnCount = 4 + ($optionLabels->count() * 2) + 1;

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
        $xml .= '<Styles>' . "\n";
        $xml .= '<Style ss:ID="Title"><Font ss:Bold="1" ss:Size="16"/><Interior ss:Color="#DCEBFF" ss:Pattern="Solid"/></Style>' . "\n";
        $xml .= '<Style ss:ID="Meta"><Font ss:Bold="1"/><Interior ss:Color="#F3F4F6" ss:Pattern="Solid"/></Style>' . "\n";
        $xml .= '<Style ss:ID="Header"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#022B63" ss:Pattern="Solid"/></Style>' . "\n";
        $xml .= '<Style ss:ID="Category"><Font ss:Bold="1"/><Interior ss:Color="#EAF1FB" ss:Pattern="Solid"/></Style>' . "\n";
        $xml .= '<Style ss:ID="Text"><Alignment ss:Vertical="Top" ss:WrapText="1"/></Style>' . "\n";
        $xml .= '<Style ss:ID="Number"><Alignment ss:Horizontal="Center" ss:Vertical="Top"/></Style>' . "\n";
        $xml .= '</Styles>' . "\n";
        $xml .= '<Worksheet ss:Name="Report EDOM"><Table>' . "\n";

        $xml .= $this->excelRow([
            ['value' => 'Report EDOM Mata Kuliah', 'style' => 'Title', 'mergeAcross' => max($columnCount - 1, 1)],
        ]);
        $xml .= $this->excelRow([
            ['value' => 'Program Studi', 'style' => 'Meta'],
            ['value' => $this->record->display_name, 'mergeAcross' => max($columnCount - 2, 0)],
        ]);
        $xml .= $this->excelRow([
            ['value' => 'Mata Kuliah', 'style' => 'Meta'],
            ['value' => $this->courseName(), 'mergeAcross' => max($columnCount - 2, 0)],
        ]);
        $xml .= $this->excelRow([
            ['value' => 'Total Respons', 'style' => 'Meta'],
            ['value' => $responses->count(), 'type' => 'Number'],
        ]);
        $xml .= $this->excelRow([
            ['value' => 'Tanggal Export', 'style' => 'Meta'],
            ['value' => $exportedAt, 'mergeAcross' => max($columnCount - 2, 0)],
        ]);
        $xml .= '<Row></Row>' . "\n";

        $header = [
            ['value' => 'No.', 'style' => 'Header'],
            ['value' => 'Kategori', 'style' => 'Header'],
            ['value' => 'Pernyataan', 'style' => 'Header'],
            ['value' => 'Jumlah Jawaban Opsi', 'style' => 'Header'],
        ];

        foreach ($optionLabels as $optionLabel) {
            $header[] = ['value' => $optionLabel.' Dipilih', 'style' => 'Header'];
            $header[] = ['value' => $optionLabel.' Persentase', 'style' => 'Header'];
        }

        $header[] = ['value' => 'Jawaban Teks/Esai', 'style' => 'Header'];
        $xml .= $this->excelRow($header);

        foreach ($rows as $index => $row) {
            $data = [
                ['value' => $index + 1, 'type' => 'Number', 'style' => 'Number'],
                ['value' => (string) $row->getAttribute('report_category'), 'style' => 'Category'],
                ['value' => (string) $row->getAttribute('report_statement'), 'style' => 'Text'],
                ['value' => (int) $row->getAttribute('option_answer_count'), 'type' => 'Number', 'style' => 'Number'],
            ];

            foreach ($optionLabels as $optionLabel) {
                $data[] = [
                    'value' => $this->selectedCountFor($row, $optionLabel),
                    'type' => 'Number',
                    'style' => 'Number',
                ];
                $data[] = [
                    'value' => $this->percentageLabelFor($row, $optionLabel),
                    'style' => 'Number',
                ];
            }

            $data[] = [
                'value' => $this->textAnswersFor($row)->join("\n"),
                'style' => 'Text',
            ];

            $xml .= $this->excelRow($data);
        }

        if ($rows->isEmpty()) {
            $xml .= $this->excelRow([
                ['value' => 'Belum ada data report untuk mata kuliah ini.', 'style' => 'Text', 'mergeAcross' => max($columnCount - 1, 1)],
            ]);
        }

        $xml .= '</Table></Worksheet></Workbook>';

        return $xml;
    }

    /**
     * @return Collection<int, EdomResponseDetail>
     */
    private function reportRows(): Collection
    {
        return $this->reportQuery()
            ->get()
            ->sortBy(fn (EdomResponseDetail $row): int => (int) $row->getAttribute('id'))
            ->values();
    }

    /**
     * @return Collection<int, string>
     */
    private function textAnswersFor(EdomResponseDetail $record): Collection
    {
        return (clone $this->detailsForReportRow($record))
            ->whereNotNull('edom_response_detail.answer_text')
            ->where('edom_response_detail.answer_text', '<>', '')
            ->pluck('edom_response_detail.answer_text')
            ->map(fn ($answer): string => trim((string) $answer))
            ->filter()
            ->values();
    }

    /**
     * @param array<int, array<string, mixed>> $cells
     */
    private function excelRow(array $cells): string
    {
        $xml = '<Row>';

        foreach ($cells as $cell) {
            $xml .= $this->excelCell(
                $cell['value'] ?? '',
                (string) ($cell['type'] ?? 'String'),
                (string) ($cell['style'] ?? ''),
                (int) ($cell['mergeAcross'] ?? 0),
            );
        }

        return $xml.'</Row>' . "\n";
    }

    private function excelCell(mixed $value, string $type = 'String', string $style = '', int $mergeAcross = 0): string
    {
        $attributes = '';

        if ($style !== '') {
            $attributes .= ' ss:StyleID="'.$this->xmlEscape($style).'"';
        }

        if ($mergeAcross > 0) {
            $attributes .= ' ss:MergeAcross="'.$mergeAcross.'"';
        }

        $dataType = $type === 'Number' && is_numeric($value) ? 'Number' : 'String';

        return '<Cell'.$attributes.'><Data ss:Type="'.$dataType.'">'.$this->xmlEscape((string) $value).'</Data></Cell>';
    }

    private function xmlEscape(string $value): string
    {
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? '';

        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function safeFilename(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value !== '' ? $value : 'report';
    }
}
