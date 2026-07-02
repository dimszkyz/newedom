<?php

namespace App\Services\Edom;

use App\Models\EdomResponse;
use App\Services\Siakad\UnwApiSiakad;
use Illuminate\Support\Collection;
use Throwable;

class EdomResultAggregator
{
    /**
     * @var array<string, Collection<int, array<string, mixed>>>
     */
    private array $penawaranCache = [];

    public function __construct(private readonly UnwApiSiakad $siakad) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function summaries(): Collection
    {
        return EdomResponse::query()
            ->join('edom_periods', 'edom_periods.id', '=', 'edom_response.edom_period_id')
            ->join('edom_settings', 'edom_settings.id', '=', 'edom_response.edom_setting_id')
            ->join('edom_response_detail', 'edom_response_detail.edom_response_id', '=', 'edom_response.id')
            ->leftJoin('edom_questions', 'edom_questions.id', '=', 'edom_response_detail.edom_question_id')
            ->leftJoin('edom_question_categories', 'edom_question_categories.id', '=', 'edom_questions.edom_question_category_id')
            ->leftJoin('edom_question_options', 'edom_question_options.id', '=', 'edom_response_detail.edom_option_id')
            ->select([
                'edom_response.edom_period_id',
                'edom_periods.year as siakad_idtahunajaran',
                'edom_periods.siakad_idsemester',
                'edom_response.edom_setting_id',
                'edom_settings.name as edom_name',
                'edom_response.siakad_idmatakuliah',
                'edom_response.siakad_idtawarmatakuliahdetail',
                'edom_questions.edom_question_category_id',
                'edom_response_detail.edom_question_id',
            ])
            ->selectRaw('COALESCE(edom_response_detail.category_name_snapshot, edom_question_categories.name) as category_name')
            ->selectRaw('COALESCE(edom_response_detail.question_statement_snapshot, edom_questions.statement) as question_statement')
            ->selectRaw('COUNT(DISTINCT edom_response.id) as respondent_count')
            ->selectRaw('COUNT(edom_response_detail.id) as answer_count')
            ->selectRaw('AVG(COALESCE(edom_response_detail.option_score_snapshot, edom_question_options.score)) as average_score')
            ->groupBy([
                'edom_response.edom_period_id',
                'edom_periods.year',
                'edom_periods.siakad_idsemester',
                'edom_response.edom_setting_id',
                'edom_settings.name',
                'edom_response.siakad_idmatakuliah',
                'edom_response.siakad_idtawarmatakuliahdetail',
                'edom_questions.edom_question_category_id',
                'edom_response_detail.edom_question_id',
                'edom_response_detail.category_name_snapshot',
                'edom_question_categories.name',
                'edom_response_detail.question_statement_snapshot',
                'edom_questions.statement',
            ])
            ->orderBy('edom_periods.year')
            ->orderBy('edom_periods.siakad_idsemester')
            ->orderBy('edom_settings.name')
            ->orderBy('edom_response.siakad_idtawarmatakuliahdetail')
            ->orderBy('category_name')
            ->orderBy('question_statement')
            ->get()
            ->map(fn (EdomResponse $row): array => $this->toSummaryRow($row))
            ->values();
    }

    /**
     * @return Collection<string, Collection<int, array<string, mixed>>>
     */
    public function groupedSummaries(): Collection
    {
        return $this->summaries()
            ->groupBy(fn (array $row): string => implode('|', [
                $row['edom_period_id'],
                $row['edom_setting_id'],
                $row['siakad_idtawarmatakuliahdetail'],
            ]))
            ->map(fn (Collection $rows): Collection => $rows
                ->sortBy([
                    ['category_name', 'asc'],
                    ['question_statement', 'asc'],
                ])
                ->values());
    }

    public function courseLabelFor(EdomResponse $row): string
    {
        $section = $this->findSection($row);
        $kode = (string) data_get($section, 'kode', '');
        $nama = (string) data_get($section, 'nama', '');

        return trim($kode.' - '.$nama) ?: 'Mata kuliah #'.$row->siakad_idmatakuliah;
    }

    public function dosenNameFor(EdomResponse $row): string
    {
        return $this->dosenName(data_get($this->findSection($row), 'dosen'));
    }

    public function dosenTeamFor(EdomResponse $row): string
    {
        return $this->dosenTeam(data_get($this->findSection($row), 'dosen_team'));
    }

    public function sectionMissingFor(EdomResponse $row): bool
    {
        return $this->findSection($row) === null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function sectionFor(
        int|string $tahunAjaran,
        int|string $semester,
        int|string $idtawarmatakuliahdetail,
        int|string $idmatakuliah
    ): ?array {
        $sections = $this->penawaran($tahunAjaran, $semester);
        $detailId = (string) $idtawarmatakuliahdetail;
        $courseId = (string) $idmatakuliah;

        $section = $sections->first(fn (array $section): bool => (string) data_get($section, 'idtawarmatakuliahdetail') === $detailId);

        if ($section !== null) {
            return $section;
        }

        return $sections->first(fn (array $section): bool => (string) data_get($section, 'idmatakuliah') === $courseId);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function penawaran(int|string $tahunAjaran, int|string $semester): Collection
    {
        $cacheKey = implode(':', [(string) $tahunAjaran, (string) $semester, 'all']);

        if (! array_key_exists($cacheKey, $this->penawaranCache)) {
            try {
                $sections = $this->siakad->penawaran((int) $tahunAjaran, (int) $semester, null);
            } catch (Throwable $exception) {
                report($exception);
                $sections = [];
            }

            $this->penawaranCache[$cacheKey] = collect($sections)
                ->filter(fn ($section): bool => is_array($section))
                ->values();
        }

        return $this->penawaranCache[$cacheKey];
    }

    /**
     * @return array<string, mixed>
     */
    private function toSummaryRow(EdomResponse $row): array
    {
        $section = $this->findSection($row);
        $averageScore = $row->average_score === null ? null : round((float) $row->average_score, 2);

        return [
            'edom_period_id' => (int) $row->edom_period_id,
            'siakad_idtahunajaran' => (int) $row->siakad_idtahunajaran,
            'siakad_idsemester' => (int) $row->siakad_idsemester,
            'edom_setting_id' => (int) $row->edom_setting_id,
            'edom_name' => (string) $row->edom_name,
            'siakad_idmatakuliah' => (int) $row->siakad_idmatakuliah,
            'siakad_idtawarmatakuliahdetail' => (int) $row->siakad_idtawarmatakuliahdetail,
            'kode' => (string) data_get($section, 'kode', '-'),
            'mata_kuliah' => (string) data_get($section, 'nama', 'Mata kuliah #'.$row->siakad_idmatakuliah),
            'course_label' => $this->courseLabelFor($row),
            'dosen' => $this->dosenName(data_get($section, 'dosen')),
            'dosen_team' => $this->dosenTeam(data_get($section, 'dosen_team')),
            'id_unw_program_studi' => data_get($section, 'id_unw_program_studi'),
            'category_name' => (string) ($row->category_name ?: 'Tanpa kategori'),
            'question_statement' => (string) ($row->question_statement ?: 'Pertanyaan tidak ditemukan'),
            'respondent_count' => (int) $row->respondent_count,
            'answer_count' => (int) $row->answer_count,
            'average_score' => $averageScore,
            'average_score_formatted' => $averageScore === null ? '-' : number_format($averageScore, 2, ',', '.'),
            'section_missing' => $section === null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findSection(EdomResponse $row): ?array
    {
        $tahunAjaran = $this->tahunAjaranFor($row);
        $semester = $this->semesterFor($row);

        if ($tahunAjaran === null || $semester === null) {
            return null;
        }

        return $this->sectionFor(
            $tahunAjaran,
            $semester,
            $row->siakad_idtawarmatakuliahdetail,
            $row->siakad_idmatakuliah,
        );
    }

    private function tahunAjaranFor(EdomResponse $row): int|string|null
    {
        $value = $row->getAttribute('siakad_idtahunajaran') ?? $row->period?->year;

        return $value === null || $value === '' ? null : $value;
    }

    private function semesterFor(EdomResponse $row): int|string|null
    {
        $value = $row->getAttribute('siakad_idsemester') ?? $row->period?->siakad_idsemester;

        return $value === null || $value === '' ? null : $value;
    }

    private function dosenName(mixed $dosen): string
    {
        if (is_array($dosen)) {
            return (string) data_get($dosen, 'nama', '-');
        }

        return is_string($dosen) && $dosen !== '' ? $dosen : '-';
    }

    private function dosenTeam(mixed $dosenTeam): string
    {
        if (is_array($dosenTeam)) {
            return collect($dosenTeam)
                ->map(function ($name): ?string {
                    if (is_array($name)) {
                        $lecturerName = trim((string) data_get($name, 'nama', ''));
                        $nidn = trim((string) data_get($name, 'nidn', ''));

                        return trim($lecturerName.($nidn !== '' ? ' ('.$nidn.')' : '')) ?: null;
                    }

                    return is_string($name) && trim($name) !== '' ? trim($name) : null;
                })
                ->filter()
                ->implode(', ');
        }

        return is_string($dosenTeam) ? $dosenTeam : '';
    }
}
