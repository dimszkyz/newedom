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
    /**
     * @var array<string, Collection<int, array<string, mixed>>>
     */
    private array $studentPeriodSections = [];

    /**
     * @var Collection<int, object>|null
     */
    private ?Collection $knownStudentPeriods = null;

    public function __construct(private readonly UnwApiSiakad $siakad) {}

    /**
     * Refresh metadata laporan dari API /edom/krs tanpa menyimpan cache section ke tabel lokal.
     *
     * @return array{student_periods:int, fetched_sections:int, updated_responses:int}
     */
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

    /**
     * Mengambil daftar mata kuliah laporan dari API /edom/krs.
     * Data ini sengaja tidak disimpan ke tabel lokal agar report mengikuti KRS terbaru.
     *
     * @return Collection<int, array{id:int, siakad_idmatakuliah:int, siakad_idtawarmatakuliahdetail:int|null, idmatakuliah:int, idtawarmatakuliahdetail:int|null, kode:string|null, nama:string, course_label:string, krs_student_count:int}>
     */
    public function reportCourseRowsForProgramStudi(ProgramStudi $programStudi): Collection
    {
        $programStudiId = $this->nullableInteger($programStudi->id_unw_program_studi);

        if ($programStudiId === null) {
            return collect();
        }

        return $this->knownStudentPeriods()
            ->flatMap(function (object $studentPeriod) use ($programStudiId): Collection {
                return $this->sectionsForStudentPeriod(
                    (string) $studentPeriod->siakad_idmahasiswa,
                    (int) $studentPeriod->siakad_idtahunajaran,
                    (int) $studentPeriod->siakad_idsemester,
                )
                    ->filter(fn (array $section): bool => $this->nullableInteger(data_get($section, 'id_unw_program_studi')) === $programStudiId)
                    ->map(function (array $section) use ($studentPeriod): array {
                        $section['siakad_idmahasiswa'] = (string) $studentPeriod->siakad_idmahasiswa;

                        return $section;
                    });
            })
            ->filter(fn (array $section): bool => $this->nullableInteger(data_get($section, 'idmatakuliah')) !== null)
            ->groupBy(fn (array $section): string => (string) $this->nullableInteger(data_get($section, 'idmatakuliah')))
            ->map(function (Collection $sections): array {
                /** @var array<string, mixed> $first */
                $first = $sections->first();
                $courseId = (int) $this->nullableInteger(data_get($first, 'idmatakuliah'));
                $detailId = $this->nullableInteger(data_get($first, 'idtawarmatakuliahdetail'));
                $code = trim((string) data_get($first, 'kode', ''));
                $name = trim((string) data_get($first, 'nama', ''));
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
                    'krs_student_count' => $sections
                        ->pluck('siakad_idmahasiswa')
                        ->filter()
                        ->unique()
                        ->count(),
                ];
            })
            ->sortBy([
                ['kode', 'asc'],
                ['nama', 'asc'],
            ])
            ->values();
    }

    public function courseNameForGroupedCourse(EdomResponse $record): string
    {
        $name = trim((string) $record->getAttribute('nama'));

        if ($name !== '') {
            return $name;
        }

        $response = $this->responseForGroupedCourse($record);

        if (! $response) {
            return 'Mata kuliah #'.$record->getAttribute('siakad_idmatakuliah');
        }

        return $this->courseNameForResponse($response);
    }

    public function courseCodeForGroupedCourse(EdomResponse $record): string
    {
        $code = trim((string) $record->getAttribute('kode'));

        if ($code !== '') {
            return $code;
        }

        $response = $this->responseForGroupedCourse($record);

        if (! $response) {
            return '-';
        }

        return $this->courseCodeForResponse($response);
    }

    public function courseLabelForGroupedCourse(EdomResponse $record): string
    {
        $label = trim((string) $record->getAttribute('course_label'));

        if ($label !== '') {
            return $label;
        }

        $response = $this->responseForGroupedCourse($record);

        if (! $response) {
            return 'Mata kuliah #'.$record->getAttribute('siakad_idmatakuliah');
        }

        return $this->courseLabelForResponse($response);
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

    /**
     * @return array<string, mixed>|null
     */
    public function sectionForResponse(EdomResponse $response): ?array
    {
        $period = $response->relationLoaded('period')
            ? $response->period
            : $response->period()->first();

        if (! $period) {
            return null;
        }

        $sections = $this->sectionsForStudentPeriod(
            (string) $response->siakad_idmahasiswa,
            (int) $period->year,
            (int) $period->siakad_idsemester,
        );

        return $this->matchingSection(
            $sections,
            $this->nullableInteger($response->siakad_idtawarmatakuliahdetail),
            $this->nullableInteger($response->siakad_idmatakuliah),
        );
    }

    /**
     * @return Collection<int, object>
     */
    private function knownStudentPeriods(): Collection
    {
        if ($this->knownStudentPeriods === null) {
            $this->knownStudentPeriods = EdomResponse::query()
                ->join('edom_periods', 'edom_periods.id', '=', 'edom_response.edom_period_id')
                ->select([
                    'edom_response.siakad_idmahasiswa',
                    'edom_periods.year as siakad_idtahunajaran',
                    'edom_periods.siakad_idsemester',
                ])
                ->distinct()
                ->get();
        }

        return $this->knownStudentPeriods;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function sectionsForStudentPeriod(string $studentId, int $tahunAjaran, int $semester): Collection
    {
        $cacheKey = 'edom-report:krs-api:'.$studentId.':'.$tahunAjaran.':'.$semester;

        if (! array_key_exists($cacheKey, $this->studentPeriodSections)) {
            try {
                $sections = Cache::remember(
                    $cacheKey,
                    now()->addMinutes(30),
                    fn (): array => $this->siakad->krs($studentId, $tahunAjaran, $semester),
                );
            } catch (Throwable $exception) {
                report($exception);
                $sections = [];
            }

            $this->studentPeriodSections[$cacheKey] = collect($sections)
                ->filter(fn (mixed $section): bool => is_array($section) && filled(data_get($section, 'idmatakuliah')))
                ->values();
        }

        return $this->studentPeriodSections[$cacheKey];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $sections
     */
    private function syncResponseProgramStudi(
        string $studentId,
        int $tahunAjaran,
        int $semester,
        Collection $sections,
    ): int {
        $periodId = EdomPeriod::query()
            ->where('year', $tahunAjaran)
            ->where('siakad_idsemester', $semester)
            ->value('id');

        if ($periodId === null) {
            return 0;
        }

        $updatedResponses = 0;
        $responses = EdomResponse::query()
            ->where('edom_period_id', $periodId)
            ->where('siakad_idmahasiswa', $studentId)
            ->get([
                'id',
                'siakad_idmatakuliah',
                'siakad_idtawarmatakuliahdetail',
                'id_unw_program_studi',
            ]);

        foreach ($responses as $response) {
            $section = $this->matchingSection(
                $sections,
                $this->nullableInteger($response->siakad_idtawarmatakuliahdetail),
                $this->nullableInteger($response->siakad_idmatakuliah),
            );
            $programStudiId = data_get($section, 'id_unw_program_studi');

            if (! filled($programStudiId) || (int) $response->id_unw_program_studi === (int) $programStudiId) {
                continue;
            }

            EdomResponse::query()
                ->whereKey($response->id)
                ->update(['id_unw_program_studi' => (int) $programStudiId]);

            $updatedResponses++;
        }

        return $updatedResponses;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $sections
     * @return array<string, mixed>|null
     */
    private function matchingSection(Collection $sections, ?int $detailId, ?int $courseId): ?array
    {
        if ($detailId !== null) {
            $section = $sections->first(
                fn (array $section): bool => $this->nullableInteger(data_get($section, 'idtawarmatakuliahdetail')) === $detailId,
            );

            if (is_array($section)) {
                return $section;
            }
        }

        if ($courseId !== null) {
            $section = $sections->first(
                fn (array $section): bool => $this->nullableInteger(data_get($section, 'idmatakuliah')) === $courseId,
            );

            if (is_array($section)) {
                return $section;
            }
        }

        return null;
    }

    private function responseForGroupedCourse(EdomResponse $record): ?EdomResponse
    {
        $responseId = $this->nullableInteger($record->getKey());

        if ($responseId === null) {
            return null;
        }

        return EdomResponse::query()->with('period')->find($responseId);
    }

    private function nullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
