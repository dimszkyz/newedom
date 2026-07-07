<?php

namespace App\Services\Edom;

use App\Models\EdomPeriod;
use App\Models\EdomResponse;
use App\Models\ProgramStudi;
use App\Services\Siakad\UnwApiSiakad;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Throwable;

class EdomKrsReportData
{
    private array $studentPeriodSections = [];
    private array $programPeriodOfferings = [];
    private ?Collection $knownStudentPeriods = null;
    private ?Collection $reportPeriods = null;
    private array $studentPeriodsByProgramStudi = [];

    public function __construct(private readonly UnwApiSiakad $siakad) {}

    public function refreshKnownResponseMetadata(): array
    {
        $studentPeriods = $this->knownStudentPeriods();
        $fetchedSections = 0;
        $updatedResponses = 0;

        foreach ($studentPeriods as $studentPeriod) {
            $sections = $this->sectionsForStudentPeriod(
                (string) $studentPeriod->siakad_idmahasiswa,
                (int) $studentPeriod->siakad_idtahunajaran,
                (int) $studentPeriod->siakad_idsemester,
            );

            $fetchedSections += $sections->count();
            $updatedResponses += $this->syncResponseProgramStudi(
                (string) $studentPeriod->siakad_idmahasiswa,
                (int) $studentPeriod->siakad_idtahunajaran,
                (int) $studentPeriod->siakad_idsemester,
                $sections,
            );
        }

        return [
            'student_periods' => $studentPeriods->count(),
            'fetched_sections' => $fetchedSections,
            'updated_responses' => $updatedResponses,
        ];
    }

    public function courseCountForProgramStudi(ProgramStudi $programStudi): int
    {
        return $this->reportCourseRowsForProgramStudi($programStudi)->count();
    }

    public function reportCourseRowsForProgramStudi(ProgramStudi $programStudi): Collection
    {
        $rows = $this->offeringCourseRowsForProgramStudi($programStudi);

        return $rows->isNotEmpty() ? $rows : $this->krsCourseRowsForProgramStudi($programStudi);
    }

    public function courseNameForGroupedCourse(EdomResponse $record): string
    {
        $name = trim((string) $record->getAttribute('nama'));

        if ($name !== '') {
            return $name;
        }

        $response = $this->responseForGroupedCourse($record);

        return $response ? $this->courseNameForResponse($response) : 'Mata kuliah #'.$record->getAttribute('siakad_idmatakuliah');
    }

    public function courseCodeForGroupedCourse(EdomResponse $record): string
    {
        $code = trim((string) $record->getAttribute('kode'));

        if ($code !== '') {
            return $code;
        }

        $response = $this->responseForGroupedCourse($record);

        return $response ? $this->courseCodeForResponse($response) : '-';
    }

    public function courseLabelForGroupedCourse(EdomResponse $record): string
    {
        $label = trim((string) $record->getAttribute('course_label'));

        if ($label !== '') {
            return $label;
        }

        $response = $this->responseForGroupedCourse($record);

        return $response ? $this->courseLabelForResponse($response) : 'Mata kuliah #'.$record->getAttribute('siakad_idmatakuliah');
    }

    public function courseNameForResponse(EdomResponse $response): string
    {
        $section = $this->sectionForResponse($response);
        $courseName = trim((string) data_get($section, 'nama', ''));

        return $courseName !== '' ? $courseName : 'Mata kuliah #'.$response->siakad_idmatakuliah;
    }

    public function courseCodeForResponse(EdomResponse $response): string
    {
        $section = $this->sectionForResponse($response);
        $courseCode = trim((string) data_get($section, 'kode', ''));

        return $courseCode !== '' ? $courseCode : '-';
    }

    public function courseLabelForResponse(EdomResponse $response): string
    {
        $section = $this->sectionForResponse($response);
        $code = trim((string) data_get($section, 'kode', ''));
        $name = trim((string) data_get($section, 'nama', ''));
        $label = trim($code.' - '.$name, ' -');

        return $label !== '' ? $label : 'Mata kuliah #'.$response->siakad_idmatakuliah;
    }

    public function sectionForResponse(EdomResponse $response): ?array
    {
        $period = $response->relationLoaded('period') ? $response->period : $response->period()->first();

        if (! $period) {
            return null;
        }

        return $this->matchingSection(
            $this->sectionsForStudentPeriod((string) $response->siakad_idmahasiswa, (int) $period->year, (int) $period->siakad_idsemester),
            $this->nullableInteger($response->siakad_idtawarmatakuliahdetail),
            $this->nullableInteger($response->siakad_idmatakuliah),
        );
    }

    private function offeringCourseRowsForProgramStudi(ProgramStudi $programStudi): Collection
    {
        $programStudiId = $this->nullableInteger($programStudi->id_unw_program_studi);

        if ($programStudiId === null) {
            return collect();
        }

        return $this->reportPeriods()
            ->flatMap(fn (EdomPeriod $period): Collection => $this->offeringsForProgramPeriod($programStudiId, (int) $period->year, (int) $period->siakad_idsemester))
            ->map(fn (array $section): ?array => $this->courseRowFromSection($section, 0))
            ->filter()
            ->groupBy(fn (array $row): string => (string) $row['siakad_idmatakuliah'])
            ->map(fn (Collection $rows): array => $rows->first())
            ->sortBy([['kode', 'asc'], ['nama', 'asc']])
            ->values();
    }

    private function krsCourseRowsForProgramStudi(ProgramStudi $programStudi): Collection
    {
        return $this->studentPeriodsForProgramStudi($programStudi)
            ->flatMap(function (object $studentPeriod): Collection {
                return $this->sectionsForStudentPeriod(
                    (string) $studentPeriod->siakad_idmahasiswa,
                    (int) $studentPeriod->siakad_idtahunajaran,
                    (int) $studentPeriod->siakad_idsemester,
                )->map(function (array $section) use ($studentPeriod): array {
                    $section['siakad_idmahasiswa'] = (string) $studentPeriod->siakad_idmahasiswa;

                    return $section;
                });
            })
            ->filter(fn (array $section): bool => $this->nullableInteger(data_get($section, 'idmatakuliah')) !== null)
            ->groupBy(fn (array $section): string => (string) $this->nullableInteger(data_get($section, 'idmatakuliah')))
            ->map(fn (Collection $sections): ?array => $this->courseRowFromSection($sections->first(), $sections->pluck('siakad_idmahasiswa')->filter()->unique()->count()))
            ->filter()
            ->sortBy([['kode', 'asc'], ['nama', 'asc']])
            ->values();
    }

    private function knownStudentPeriods(): Collection
    {
        if ($this->knownStudentPeriods === null) {
            $this->knownStudentPeriods = EdomResponse::query()
                ->join('edom_periods', 'edom_periods.id', '=', 'edom_response.edom_period_id')
                ->select(['edom_response.siakad_idmahasiswa', 'edom_periods.year as siakad_idtahunajaran', 'edom_periods.siakad_idsemester'])
                ->distinct()
                ->get();
        }

        return $this->knownStudentPeriods;
    }

    private function reportPeriods(): Collection
    {
        if ($this->reportPeriods === null) {
            $this->reportPeriods = EdomPeriod::query()
                ->orderByDesc('year')
                ->orderByDesc('siakad_idsemester')
                ->get();
        }

        return $this->reportPeriods;
    }

    private function studentPeriodsForProgramStudi(ProgramStudi $programStudi): Collection
    {
        $programStudiId = $this->nullableInteger($programStudi->id_unw_program_studi);

        if ($programStudiId === null) {
            return collect();
        }

        $cacheKey = (string) $programStudiId;

        if (! array_key_exists($cacheKey, $this->studentPeriodsByProgramStudi)) {
            $this->studentPeriodsByProgramStudi[$cacheKey] = EdomResponse::query()
                ->join('edom_periods', 'edom_periods.id', '=', 'edom_response.edom_period_id')
                ->where('edom_response.id_unw_program_studi', $programStudiId)
                ->select(['edom_response.siakad_idmahasiswa', 'edom_periods.year as siakad_idtahunajaran', 'edom_periods.siakad_idsemester'])
                ->distinct()
                ->get();
        }

        return $this->studentPeriodsByProgramStudi[$cacheKey];
    }

    private function sectionsForStudentPeriod(string $studentId, int $tahunAjaran, int $semester): Collection
    {
        $cacheKey = 'edom-report:krs-api-v2:'.$studentId.':'.$tahunAjaran.':'.$semester;

        if (! array_key_exists($cacheKey, $this->studentPeriodSections)) {
            try {
                $sections = Cache::remember($cacheKey, now()->addMinutes(30), fn (): array => $this->siakad->krs($studentId, $tahunAjaran, $semester));
            } catch (Throwable $exception) {
                report($exception);
                $sections = [];
            }

            $this->studentPeriodSections[$cacheKey] = collect($sections)->filter(fn (mixed $section): bool => is_array($section) && filled(data_get($section, 'idmatakuliah')))->values();
        }

        return $this->studentPeriodSections[$cacheKey];
    }

    private function offeringsForProgramPeriod(int $programStudiId, int $tahunAjaran, int $semester): Collection
    {
        $localCacheKey = $programStudiId.':'.$tahunAjaran.':'.$semester;
        $cacheKey = 'edom-report:penawaran-api:'.$localCacheKey;

        if (! array_key_exists($localCacheKey, $this->programPeriodOfferings)) {
            try {
                $offerings = Cache::remember($cacheKey, now()->addMinutes(30), fn (): array => $this->siakad->penawaran($tahunAjaran, $semester, $programStudiId));
            } catch (Throwable $exception) {
                report($exception);
                $offerings = [];
            }

            $this->programPeriodOfferings[$localCacheKey] = collect($offerings)->filter(fn (mixed $section): bool => is_array($section))->values();
        }

        return $this->programPeriodOfferings[$localCacheKey];
    }

    private function courseRowFromSection(array $section, int $studentCount): ?array
    {
        $courseId = $this->nullableInteger(data_get($section, 'idmatakuliah') ?? data_get($section, 'id_matakuliah') ?? data_get($section, 'mata_kuliah_id') ?? data_get($section, 'matakuliah.id') ?? data_get($section, 'mata_kuliah.id'));

        if ($courseId === null) {
            return null;
        }

        $detailId = $this->nullableInteger(data_get($section, 'idtawarmatakuliahdetail') ?? data_get($section, 'id_tawar_matakuliah_detail') ?? data_get($section, 'idtawaranmatakuliahdetail') ?? data_get($section, 'tawar_matakuliah_detail_id'));
        $code = trim((string) (data_get($section, 'kode') ?? data_get($section, 'kode_matakuliah') ?? data_get($section, 'matakuliah.kode') ?? data_get($section, 'mata_kuliah.kode') ?? ''));
        $name = trim((string) (data_get($section, 'nama') ?? data_get($section, 'nama_matakuliah') ?? data_get($section, 'matakuliah.nama') ?? data_get($section, 'mata_kuliah.nama') ?? ''));
        $label = trim($code.' - '.$name, ' -');

        return [
            'id' => $courseId,
            'siakad_idmatakuliah' => $courseId,
            'siakad_idtawarmatakuliahdetail' => $detailId,
            'idmatakuliah' => $courseId,
            'idtawarmatakuliahdetail' => $detailId,
            'kode' => $code !== '' ? $code : null,
            'nama' => $name !== '' ? $name : 'Mata kuliah #'.$courseId,
            'course_label' => $label !== '' ? $label : 'Mata kuliah #'.$courseId,
            'krs_student_count' => $studentCount,
        ];
    }

    private function syncResponseProgramStudi(string $studentId, int $tahunAjaran, int $semester, Collection $sections): int
    {
        $periodId = EdomPeriod::query()->where('year', $tahunAjaran)->where('siakad_idsemester', $semester)->value('id');

        if ($periodId === null) {
            return 0;
        }

        $updatedResponses = 0;
        $responses = EdomResponse::query()->where('edom_period_id', $periodId)->where('siakad_idmahasiswa', $studentId)->get(['id', 'siakad_idmatakuliah', 'siakad_idtawarmatakuliahdetail', 'id_unw_program_studi']);

        foreach ($responses as $response) {
            $section = $this->matchingSection($sections, $this->nullableInteger($response->siakad_idtawarmatakuliahdetail), $this->nullableInteger($response->siakad_idmatakuliah));
            $programStudiId = data_get($section, 'id_unw_program_studi');

            if (! filled($programStudiId) || (int) $response->id_unw_program_studi === (int) $programStudiId) {
                continue;
            }

            EdomResponse::query()->whereKey($response->id)->update(['id_unw_program_studi' => (int) $programStudiId]);
            $updatedResponses++;
        }

        return $updatedResponses;
    }

    private function matchingSection(Collection $sections, ?int $detailId, ?int $courseId): ?array
    {
        if ($detailId !== null) {
            $section = $sections->first(fn (array $section): bool => $this->nullableInteger(data_get($section, 'idtawarmatakuliahdetail')) === $detailId);

            if (is_array($section)) {
                return $section;
            }
        }

        if ($courseId !== null) {
            $section = $sections->first(fn (array $section): bool => $this->nullableInteger(data_get($section, 'idmatakuliah')) === $courseId);

            if (is_array($section)) {
                return $section;
            }
        }

        return null;
    }

    private function responseForGroupedCourse(EdomResponse $record): ?EdomResponse
    {
        $responseId = $this->nullableInteger($record->getKey());

        return $responseId === null ? null : EdomResponse::query()->with('period')->find($responseId);
    }

    private function nullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
