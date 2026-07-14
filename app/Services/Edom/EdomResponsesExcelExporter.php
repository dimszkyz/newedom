<?php

namespace App\Services\Edom;

use App\Models\EdomResponse;
use App\Models\EdomResponseDetail;
use App\Models\ProgramStudi;
use Illuminate\Support\Collection;

class EdomResponsesExcelExporter
{
    public function toXml(): string
    {
        $responses = $this->responses();
        $programStudis = ProgramStudi::query()
            ->whereNotNull('id_unw_program_studi')
            ->get()
            ->keyBy(fn (ProgramStudi $programStudi): string => (string) $programStudi->id_unw_program_studi);
        $metadata = app(EdomResponseMetadata::class);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
        $xml .= $this->styles();
        $xml .= $this->summarySheet($responses, $programStudis, $metadata);
        $xml .= $this->detailSheet($responses, $programStudis, $metadata);

        return $xml.'</Workbook>';
    }

    /**
     * @return Collection<int, EdomResponse>
     */
    private function responses(): Collection
    {
        return EdomResponse::query()
            ->with([
                'period',
                'edomSettings',
                'details.question.category',
                'details.questionOption',
            ])
            ->orderBy('siakad_idmahasiswa')
            ->orderBy('edom_period_id')
            ->orderBy('edom_setting_id')
            ->orderBy('siakad_idmatakuliah')
            ->orderBy('submitted_at')
            ->get();
    }

    /**
     * @param Collection<int, EdomResponse> $responses
     * @param Collection<string, ProgramStudi> $programStudis
     */
    private function summarySheet(Collection $responses, Collection $programStudis, EdomResponseMetadata $metadata): string
    {
        $groups = $responses
            ->groupBy(fn (EdomResponse $response): string => implode(':', [
                (string) $response->siakad_idmahasiswa,
                (string) $response->edom_period_id,
                (string) $response->edom_setting_id,
            ]))
            ->values();

        $xml = $this->openSheet('Ringkasan Response');
        $xml .= $this->row([
            ['value' => 'Ringkasan EDOM Response', 'style' => 'Title', 'mergeAcross' => 9],
        ]);
        $xml .= $this->row([
            ['value' => 'Tanggal Export', 'style' => 'Meta'],
            ['value' => now()->format('d/m/Y H:i:s'), 'mergeAcross' => 8],
        ]);
        $xml .= $this->row([
            ['value' => 'Total Kelompok Response', 'style' => 'Meta'],
            ['value' => $groups->count(), 'type' => 'Number'],
        ]);
        $xml .= $this->row([
            ['value' => 'Total Mata Kuliah Terisi', 'style' => 'Meta'],
            ['value' => $responses->count(), 'type' => 'Number'],
        ]);
        $xml .= '<Row></Row>' . "\n";
        $xml .= $this->row([
            ['value' => 'No.', 'style' => 'Header'],
            ['value' => 'Nama Mahasiswa', 'style' => 'Header'],
            ['value' => 'NIM', 'style' => 'Header'],
            ['value' => 'ID Mahasiswa SIAKAD', 'style' => 'Header'],
            ['value' => 'Tahun Ajaran', 'style' => 'Header'],
            ['value' => 'Semester', 'style' => 'Header'],
            ['value' => 'Nama EDOM', 'style' => 'Header'],
            ['value' => 'Program Studi', 'style' => 'Header'],
            ['value' => 'Jumlah Mata Kuliah', 'style' => 'Header'],
            ['value' => 'Submit Terakhir', 'style' => 'Header'],
        ]);

        foreach ($groups as $index => $group) {
            /** @var EdomResponse|null $first */
            $first = $group->first();

            if (! $first) {
                continue;
            }

            $latest = $group
                ->sortByDesc(fn (EdomResponse $response): int => $response->submitted_at?->getTimestamp() ?? 0)
                ->first();
            $courseCount = $group
                ->unique(fn (EdomResponse $response): string => (string) (
                    $response->siakad_idtawarmatakuliahdetail
                    ?: $response->siakad_idmatakuliah
                    ?: $response->id
                ))
                ->count();

            $xml .= $this->row([
                ['value' => $index + 1, 'type' => 'Number', 'style' => 'Number'],
                ['value' => $metadata->studentNameFor($first), 'style' => 'Text'],
                ['value' => $metadata->studentNimFor($first), 'style' => 'Text'],
                ['value' => (string) $first->siakad_idmahasiswa, 'style' => 'Text'],
                ['value' => $metadata->tahunAjaranFor($first), 'style' => 'Number'],
                ['value' => $metadata->semesterNameFor($first), 'style' => 'Text'],
                ['value' => $first->edomSettings?->name ?: '-', 'style' => 'Text'],
                ['value' => $this->programStudiLabel($first, $programStudis), 'style' => 'Text'],
                ['value' => $courseCount, 'type' => 'Number', 'style' => 'Number'],
                ['value' => $latest?->submitted_at?->format('d/m/Y H:i:s') ?: '-', 'style' => 'Text'],
            ]);
        }

        if ($groups->isEmpty()) {
            $xml .= $this->row([
                ['value' => 'Belum ada EDOM Response untuk diexport.', 'style' => 'Text', 'mergeAcross' => 9],
            ]);
        }

        return $xml.$this->closeSheet();
    }

    /**
     * @param Collection<int, EdomResponse> $responses
     * @param Collection<string, ProgramStudi> $programStudis
     */
    private function detailSheet(Collection $responses, Collection $programStudis, EdomResponseMetadata $metadata): string
    {
        $xml = $this->openSheet('Detail Jawaban');
        $xml .= $this->row([
            ['value' => 'Detail Jawaban EDOM Response', 'style' => 'Title', 'mergeAcross' => 15],
        ]);
        $xml .= $this->row([
            ['value' => 'No.', 'style' => 'Header'],
            ['value' => 'Nama Mahasiswa', 'style' => 'Header'],
            ['value' => 'NIM', 'style' => 'Header'],
            ['value' => 'ID Mahasiswa SIAKAD', 'style' => 'Header'],
            ['value' => 'Tahun Ajaran', 'style' => 'Header'],
            ['value' => 'Semester', 'style' => 'Header'],
            ['value' => 'Nama EDOM', 'style' => 'Header'],
            ['value' => 'Program Studi', 'style' => 'Header'],
            ['value' => 'Mata Kuliah', 'style' => 'Header'],
            ['value' => 'Kategori', 'style' => 'Header'],
            ['value' => 'Pertanyaan', 'style' => 'Header'],
            ['value' => 'Tipe Soal', 'style' => 'Header'],
            ['value' => 'Jawaban Opsi', 'style' => 'Header'],
            ['value' => 'Nilai', 'style' => 'Header'],
            ['value' => 'Jawaban Esai', 'style' => 'Header'],
            ['value' => 'Waktu Submit', 'style' => 'Header'],
        ]);

        $rowNumber = 1;

        foreach ($responses as $response) {
            if ($response->details->isEmpty()) {
                $xml .= $this->detailRow($rowNumber++, $response, null, $programStudis, $metadata);

                continue;
            }

            foreach ($response->details as $detail) {
                $xml .= $this->detailRow($rowNumber++, $response, $detail, $programStudis, $metadata);
            }
        }

        if ($responses->isEmpty()) {
            $xml .= $this->row([
                ['value' => 'Belum ada detail jawaban EDOM Response untuk diexport.', 'style' => 'Text', 'mergeAcross' => 15],
            ]);
        }

        return $xml.$this->closeSheet();
    }

    /**
     * @param Collection<string, ProgramStudi> $programStudis
     */
    private function detailRow(
        int $rowNumber,
        EdomResponse $response,
        ?EdomResponseDetail $detail,
        Collection $programStudis,
        EdomResponseMetadata $metadata,
    ): string {
        $questionType = $detail ? $this->questionTypeFor($detail) : null;
        $isEssay = in_array($questionType, ['text', 'essay', 'esai'], true);

        return $this->row([
            ['value' => $rowNumber, 'type' => 'Number', 'style' => 'Number'],
            ['value' => $metadata->studentNameFor($response), 'style' => 'Text'],
            ['value' => $metadata->studentNimFor($response), 'style' => 'Text'],
            ['value' => (string) $response->siakad_idmahasiswa, 'style' => 'Text'],
            ['value' => $metadata->tahunAjaranFor($response), 'style' => 'Number'],
            ['value' => $metadata->semesterNameFor($response), 'style' => 'Text'],
            ['value' => $response->edomSettings?->name ?: '-', 'style' => 'Text'],
            ['value' => $this->programStudiLabel($response, $programStudis), 'style' => 'Text'],
            ['value' => $metadata->krsCourseLabelFor($response), 'style' => 'Text'],
            ['value' => $detail?->category_name_for_display ?: '-', 'style' => 'Text'],
            ['value' => $detail?->question_statement_for_display ?: '-', 'style' => 'Text'],
            ['value' => $this->questionTypeLabel($questionType), 'style' => 'Text'],
            ['value' => $isEssay ? '' : ($detail?->option_name_for_display ?: '-'), 'style' => 'Text'],
            ['value' => $isEssay ? '' : ($detail?->option_score_for_display ?? ''), 'type' => 'Number', 'style' => 'Number'],
            ['value' => $isEssay ? trim((string) $detail?->answer_text) : '', 'style' => 'Text'],
            ['value' => $response->submitted_at?->format('d/m/Y H:i:s') ?: '-', 'style' => 'Text'],
        ]);
    }

    private function questionTypeFor(EdomResponseDetail $detail): string
    {
        $type = trim((string) (
            $detail->question_type_snapshot
            ?: $detail->question?->question_type
            ?: (filled($detail->answer_text) ? 'text' : 'option')
        ));

        return strtolower($type);
    }

    private function questionTypeLabel(?string $questionType): string
    {
        return in_array($questionType, ['text', 'essay', 'esai'], true)
            ? 'Esai'
            : ($questionType === null ? '-' : 'Pilihan / Opsi');
    }

    /**
     * @param Collection<string, ProgramStudi> $programStudis
     */
    private function programStudiLabel(EdomResponse $response, Collection $programStudis): string
    {
        if ($response->id_unw_program_studi === null) {
            return '-';
        }

        $programStudi = $programStudis->get((string) $response->id_unw_program_studi);

        return $programStudi?->display_name ?: 'Program Studi #'.$response->id_unw_program_studi;
    }

    private function styles(): string
    {
        return '<Styles>' . "\n"
            .'<Style ss:ID="Title"><Font ss:Bold="1" ss:Size="16"/><Interior ss:Color="#DCEBFF" ss:Pattern="Solid"/></Style>' . "\n"
            .'<Style ss:ID="Meta"><Font ss:Bold="1"/><Interior ss:Color="#F3F4F6" ss:Pattern="Solid"/></Style>' . "\n"
            .'<Style ss:ID="Header"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#022B63" ss:Pattern="Solid"/></Style>' . "\n"
            .'<Style ss:ID="Text"><Alignment ss:Vertical="Top" ss:WrapText="1"/></Style>' . "\n"
            .'<Style ss:ID="Number"><Alignment ss:Horizontal="Center" ss:Vertical="Top"/></Style>' . "\n"
            .'</Styles>' . "\n";
    }

    private function openSheet(string $sheetName): string
    {
        return '<Worksheet ss:Name="'.$this->xmlEscape($sheetName).'"><Table>' . "\n";
    }

    private function closeSheet(): string
    {
        return '</Table></Worksheet>' . "\n";
    }

    /**
     * @param array<int, array<string, mixed>> $cells
     */
    private function row(array $cells): string
    {
        $xml = '<Row>';

        foreach ($cells as $cell) {
            $xml .= $this->cell(
                $cell['value'] ?? '',
                (string) ($cell['type'] ?? 'String'),
                (string) ($cell['style'] ?? ''),
                (int) ($cell['mergeAcross'] ?? 0),
            );
        }

        return $xml.'</Row>' . "\n";
    }

    private function cell(mixed $value, string $type = 'String', string $style = '', int $mergeAcross = 0): string
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
