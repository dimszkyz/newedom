<?php

namespace App\Filament\Resources\EdomReports\Pages;

use App\Filament\Resources\EdomReports\EdomReportResource;
use App\Models\EdomResponse;
use App\Models\EdomResponseDetail;
use App\Models\ProgramStudi;
use App\Services\Edom\EdomKrsSectionSyncService;
use App\Services\Edom\EdomResponseMetadata;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListEdomReports extends ListRecords
{
    protected static string $resource = EdomReportResource::class;

    public function mount(): void
    {
        parent::mount();

        app(EdomKrsSectionSyncService::class)->syncKnownStudentPeriods();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportAllExcel')
                ->label('Export Semua Reports')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn (): StreamedResponse => $this->exportAllExcel()),
        ];
    }

    public function exportAllExcel(): StreamedResponse
    {
        $filename = 'edom-reports-semua-'.now()->format('Ymd-His').'.xls';

        return response()->streamDownload(
            function (): void {
                echo $this->allReportsExcelXml();
            },
            $filename,
            [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            ],
        );
    }

    private function allReportsExcelXml(): string
    {
        $responses = $this->allReportResponses();
        $optionLabels = $this->optionLabelsForResponseIds($responses->pluck('id'));
        $programStudis = ProgramStudi::query()
            ->whereNotNull('id_unw_program_studi')
            ->orderBy('jenjang_nama_singkat')
            ->orderBy('nama')
            ->get()
            ->keyBy(fn (ProgramStudi $programStudi): string => (string) $programStudi->id_unw_program_studi);
        $courseGroups = $this->courseGroupsForResponses($responses, $programStudis);
        $exportedAt = now()->format('d/m/Y H:i:s');
        $columnCount = 7 + ($optionLabels->count() * 2) + 1;

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
        $xml .= '<Styles>' . "\n";
        $xml .= '<Style ss:ID="Title"><Font ss:Bold="1" ss:Size="16"/><Interior ss:Color="#DCEBFF" ss:Pattern="Solid"/></Style>' . "\n";
        $xml .= '<Style ss:ID="Meta"><Font ss:Bold="1"/><Interior ss:Color="#F3F4F6" ss:Pattern="Solid"/></Style>' . "\n";
        $xml .= '<Style ss:ID="Header"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#022B63" ss:Pattern="Solid"/></Style>' . "\n";
        $xml .= '<Style ss:ID="Program"><Font ss:Bold="1"/><Interior ss:Color="#FFF4D6" ss:Pattern="Solid"/></Style>' . "\n";
        $xml .= '<Style ss:ID="Category"><Font ss:Bold="1"/><Interior ss:Color="#EAF1FB" ss:Pattern="Solid"/></Style>' . "\n";
        $xml .= '<Style ss:ID="Text"><Alignment ss:Vertical="Top" ss:WrapText="1"/></Style>' . "\n";
        $xml .= '<Style ss:ID="Number"><Alignment ss:Horizontal="Center" ss:Vertical="Top"/></Style>' . "\n";
        $xml .= '</Styles>' . "\n";
        $xml .= '<Worksheet ss:Name="Semua Report EDOM"><Table>' . "\n";

        $xml .= $this->excelRow([
            ['value' => 'Semua Report EDOM', 'style' => 'Title', 'mergeAcross' => max($columnCount - 1, 1)],
        ]);
        $xml .= $this->excelRow([
            ['value' => 'Total Program Studi', 'style' => 'Meta'],
            ['value' => $courseGroups->pluck('program_studi_label')->unique()->count(), 'type' => 'Number'],
        ]);
        $xml .= $this->excelRow([
            ['value' => 'Total Mata Kuliah Report', 'style' => 'Meta'],
            ['value' => $courseGroups->count(), 'type' => 'Number'],
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
            ['value' => 'Program Studi', 'style' => 'Header'],
            ['value' => 'Mata Kuliah', 'style' => 'Header'],
            ['value' => 'Kategori', 'style' => 'Header'],
            ['value' => 'Pernyataan', 'style' => 'Header'],
            ['value' => 'Respons Mata Kuliah', 'style' => 'Header'],
            ['value' => 'Jumlah Jawaban Opsi', 'style' => 'Header'],
        ];

        foreach ($optionLabels as $optionLabel) {
            $header[] = ['value' => $optionLabel.' Dipilih', 'style' => 'Header'];
            $header[] = ['value' => $optionLabel.' Persentase', 'style' => 'Header'];
        }

        $header[] = ['value' => 'Jawaban Teks/Esai', 'style' => 'Header'];
        $xml .= $this->excelRow($header);

        $rowNumber = 1;

        foreach ($courseGroups as $courseGroup) {
            $responseIds = $courseGroup['response_ids'];
            $reportRows = $this->reportRowsForResponseIds($responseIds);

            if ($reportRows->isEmpty()) {
                $xml .= $this->excelRow([
                    ['value' => $rowNumber++, 'type' => 'Number', 'style' => 'Number'],
                    ['value' => $courseGroup['program_studi_label'], 'style' => 'Program'],
                    ['value' => $courseGroup['course_label'], 'style' => 'Program'],
                    ['value' => '-', 'style' => 'Text'],
                    ['value' => 'Belum ada detail jawaban untuk report ini.', 'style' => 'Text'],
                    ['value' => $responseIds->count(), 'type' => 'Number', 'style' => 'Number'],
                    ['value' => 0, 'type' => 'Number', 'style' => 'Number'],
                    ['value' => '', 'style' => 'Text', 'mergeAcross' => max(($optionLabels->count() * 2), 0)],
                ]);

                continue;
            }

            foreach ($reportRows as $reportRow) {
                $data = [
                    ['value' => $rowNumber++, 'type' => 'Number', 'style' => 'Number'],
                    ['value' => $courseGroup['program_studi_label'], 'style' => 'Program'],
                    ['value' => $courseGroup['course_label'], 'style' => 'Text'],
                    ['value' => (string) $reportRow->getAttribute('report_category'), 'style' => 'Category'],
                    ['value' => (string) $reportRow->getAttribute('report_statement'), 'style' => 'Text'],
                    ['value' => $responseIds->count(), 'type' => 'Number', 'style' => 'Number'],
                    ['value' => (int) $reportRow->getAttribute('option_answer_count'), 'type' => 'Number', 'style' => 'Number'],
                ];

                foreach ($optionLabels as $optionLabel) {
                    $data[] = [
                        'value' => $this->selectedCountFor($responseIds, $reportRow, $optionLabel),
                        'type' => 'Number',
                        'style' => 'Number',
                    ];
                    $data[] = [
                        'value' => $this->percentageLabelFor($responseIds, $reportRow, $optionLabel),
                        'style' => 'Number',
                    ];
                }

                $data[] = [
                    'value' => $this->textAnswersFor($responseIds, $reportRow)->join("\n"),
                    'style' => 'Text',
                ];

                $xml .= $this->excelRow($data);
            }
        }

        if ($courseGroups->isEmpty()) {
            $xml .= $this->excelRow([
                ['value' => 'Belum ada data EDOM Reports untuk diexport.', 'style' => 'Text', 'mergeAcross' => max($columnCount - 1, 1)],
            ]);
        }

        $xml .= '</Table></Worksheet></Workbook>';

        return $xml;
    }

    /**
     * @return Collection<int, EdomResponse>
     */
    private function allReportResponses(): Collection
    {
        return EdomResponse::query()
            ->with(['period', 'edomSettings'])
            ->whereNotNull('id_unw_program_studi')
            ->orderBy('id_unw_program_studi')
            ->orderBy('siakad_idmatakuliah')
            ->orderBy('submitted_at')
            ->get();
    }

    /**
     * @param Collection<int, EdomResponse> $responses
     * @param Collection<string, ProgramStudi> $programStudis
     * @return Collection<int, array{program_studi_label: string, course_label: string, response_ids: Collection<int, int>}>
     */
    private function courseGroupsForResponses(Collection $responses, Collection $programStudis): Collection
    {
        $metadata = app(EdomResponseMetadata::class);

        return $responses
            ->groupBy(fn (EdomResponse $response): string => (string) $response->id_unw_program_studi)
            ->flatMap(function (Collection $programResponses, string $programStudiId) use ($programStudis, $metadata): Collection {
                $programStudi = $programStudis->get($programStudiId);
                $programStudiLabel = $programStudi?->display_name ?: 'Program Studi #'.$programStudiId;

                return $programResponses
                    ->groupBy(fn (EdomResponse $response): string => (string) $response->siakad_idmatakuliah)
                    ->map(function (Collection $courseResponses) use ($programStudiLabel, $metadata): array {
                        /** @var EdomResponse|null $firstResponse */
                        $firstResponse = $courseResponses->first();

                        return [
                            'program_studi_label' => $programStudiLabel,
                            'course_label' => $firstResponse ? $metadata->courseNameFor($firstResponse) : 'Mata Kuliah',
                            'response_ids' => $courseResponses->pluck('id')->map(fn ($id): int => (int) $id)->values(),
                        ];
                    })
                    ->values();
            })
            ->values();
    }

    /**
     * @param Collection<int, int|string> $responseIds
     * @return Collection<int, string>
     */
    private function optionLabelsForResponseIds(Collection $responseIds): Collection
    {
        $ids = $responseIds->map(fn ($id): int => (int) $id)->filter()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return EdomResponseDetail::query()
            ->leftJoin('edom_question_options', 'edom_question_options.id', '=', 'edom_response_detail.edom_option_id')
            ->whereIn('edom_response_detail.edom_response_id', $ids->all())
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

    /**
     * @param Collection<int, int> $responseIds
     * @return Collection<int, EdomResponseDetail>
     */
    private function reportRowsForResponseIds(Collection $responseIds): Collection
    {
        return $this->reportQueryForResponseIds($responseIds)
            ->get()
            ->sortBy(fn (EdomResponseDetail $row): int => (int) $row->getAttribute('id'))
            ->values();
    }

    /**
     * @param Collection<int, int> $responseIds
     */
    private function reportQueryForResponseIds(Collection $responseIds): Builder
    {
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
     * @param Collection<int, int> $responseIds
     */
    private function selectedCountFor(Collection $responseIds, EdomResponseDetail $record, string $optionLabel): int
    {
        return (clone $this->detailsForReportRow($responseIds, $record))
            ->whereRaw('COALESCE(edom_response_detail.option_name_snapshot, edom_question_options.name) = ?', [$optionLabel])
            ->count();
    }

    /**
     * @param Collection<int, int> $responseIds
     */
    private function percentageLabelFor(Collection $responseIds, EdomResponseDetail $record, string $optionLabel): string
    {
        $total = (clone $this->detailsForReportRow($responseIds, $record))
            ->whereRaw('COALESCE(edom_response_detail.option_name_snapshot, edom_question_options.name) IS NOT NULL')
            ->count();

        if ($total <= 0) {
            return '0%';
        }

        $percentage = ($this->selectedCountFor($responseIds, $record, $optionLabel) / $total) * 100;

        if ($percentage === round($percentage)) {
            return number_format($percentage, 0, ',', '.').'%';
        }

        return number_format($percentage, 2, ',', '.').'%';
    }

    /**
     * @param Collection<int, int> $responseIds
     */
    private function detailsForReportRow(Collection $responseIds, EdomResponseDetail $record): Builder
    {
        return EdomResponseDetail::query()
            ->leftJoin('edom_questions', 'edom_questions.id', '=', 'edom_response_detail.edom_question_id')
            ->leftJoin('edom_question_categories', 'edom_question_categories.id', '=', 'edom_questions.edom_question_category_id')
            ->leftJoin('edom_question_options', 'edom_question_options.id', '=', 'edom_response_detail.edom_option_id')
            ->whereIn('edom_response_detail.edom_response_id', $responseIds->all())
            ->whereRaw("COALESCE(edom_response_detail.category_name_snapshot, edom_question_categories.name, 'Kategori dihapus') = ?", [$record->getAttribute('report_category')])
            ->whereRaw("COALESCE(edom_response_detail.question_statement_snapshot, edom_questions.statement, 'Pertanyaan dihapus') = ?", [$record->getAttribute('report_statement')]);
    }

    /**
     * @param Collection<int, int> $responseIds
     * @return Collection<int, string>
     */
    private function textAnswersFor(Collection $responseIds, EdomResponseDetail $record): Collection
    {
        return (clone $this->detailsForReportRow($responseIds, $record))
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
}
