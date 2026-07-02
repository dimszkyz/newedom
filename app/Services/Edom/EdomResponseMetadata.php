<?php

namespace App\Services\Edom;

use App\Models\EdomKrsSection;
use App\Models\EdomResponse;
use App\Services\Siakad\UnwApiSiakad;
use Illuminate\Support\Facades\Cache;
use Throwable;

class EdomResponseMetadata
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $studentProfiles = [];

    /**
     * @var array<string, string>|null
     */
    private ?array $semesterLabels = null;

    /**
     * @var array<string, array<int, string>>
     */
    private array $studentGroupCourseLabels = [];

    public function __construct(
        private readonly UnwApiSiakad $siakad,
        private readonly EdomResultAggregator $aggregator,
    ) {}

    public function studentNameFor(EdomResponse $response): string
    {
        $profile = $this->studentProfileFor($response);
        $name = trim((string) data_get($profile, 'nama', ''));

        return $name !== '' ? $name : 'Mahasiswa #'.$response->siakad_idmahasiswa;
    }

    public function studentNimFor(EdomResponse $response): string
    {
        $profile = $this->studentProfileFor($response);
        $nim = trim((string) data_get($profile, 'npm', ''));

        return $nim !== '' ? $nim : '-';
    }

    public function semesterNameFor(EdomResponse $response): string
    {
        $semesterId = $this->semesterIdFor($response);

        if ($semesterId === null) {
            return '-';
        }

        $label = trim((string) ($this->semesterLabels()[(string) $semesterId] ?? ''));

        return $label !== '' ? $label : 'Semester '.$semesterId;
    }

    public function tahunAjaranFor(EdomResponse $response): string
    {
        $year = $response->getAttribute('siakad_idtahunajaran') ?? $response->period?->year;

        return $year === null || $year === '' ? '-' : (string) $year;
    }

    public function courseLabelFor(EdomResponse $response): string
    {
        return $this->aggregator->courseLabelFor($response);
    }

    public function krsCourseLabelFor(EdomResponse $response): string
    {
        $period = $response->period;

        if ($period) {
            $section = EdomKrsSection::query()
                ->where('siakad_idmahasiswa', (string) $response->siakad_idmahasiswa)
                ->where('siakad_idtahunajaran', (int) $period->year)
                ->where('siakad_idsemester', (int) $period->siakad_idsemester)
                ->where('idmatakuliah', (int) $response->siakad_idmatakuliah)
                ->when(
                    filled($response->siakad_idtawarmatakuliahdetail),
                    fn ($query) => $query->orderByRaw(
                        'CASE WHEN idtawarmatakuliahdetail = ? THEN 0 ELSE 1 END',
                        [(int) $response->siakad_idtawarmatakuliahdetail]
                    )
                )
                ->first();

            if ($section) {
                return $section->course_label;
            }
        }

        return $this->courseLabelFor($response);
    }

    public function courseNameFor(EdomResponse $response): string
    {
        $section = $this->sectionFor($response);
        $courseName = trim((string) data_get($section, 'nama', ''));

        return $courseName !== '' ? $courseName : 'Mata kuliah #'.$response->siakad_idmatakuliah;
    }

    /**
     * @return array<int, string>
     */
    public function courseLabelsForStudentGroup(EdomResponse $response): array
    {
        $groupKey = $this->studentGroupKey($response);

        if (array_key_exists($groupKey, $this->studentGroupCourseLabels)) {
            return $this->studentGroupCourseLabels[$groupKey];
        }

        $responses = EdomResponse::query()
            ->where('siakad_idmahasiswa', $response->siakad_idmahasiswa)
            ->where('edom_period_id', $response->edom_period_id)
            ->where('edom_setting_id', $response->edom_setting_id)
            ->with('period')
            ->orderBy('siakad_idmatakuliah')
            ->get()
            ->unique(fn (EdomResponse $item): string => (string) $item->siakad_idmatakuliah)
            ->values();

        $period = $response->period;
        $cachedSections = EdomKrsSection::query()
            ->where('siakad_idmahasiswa', (string) $response->siakad_idmahasiswa)
            ->when($period, fn ($query) => $query
                ->where('siakad_idtahunajaran', (int) $period->year)
                ->where('siakad_idsemester', (int) $period->siakad_idsemester))
            ->whereIn('idmatakuliah', $responses->pluck('siakad_idmatakuliah')->all())
            ->orderBy('id')
            ->get()
            ->unique(fn (EdomKrsSection $section): string => (string) $section->idmatakuliah)
            ->keyBy(fn (EdomKrsSection $section): string => (string) $section->idmatakuliah);

        return $this->studentGroupCourseLabels[$groupKey] = $responses
            ->map(function (EdomResponse $item) use ($cachedSections): string {
                $section = $cachedSections->get((string) $item->siakad_idmatakuliah);

                return $section?->course_label ?: $this->krsCourseLabelFor($item);
            })
            ->unique()
            ->values()
            ->all();
    }

    public function courseCountForStudentGroup(EdomResponse $response): int
    {
        return count($this->courseLabelsForStudentGroup($response));
    }

    public function dosenNameFor(EdomResponse $response): string
    {
        return $this->aggregator->dosenNameFor($response);
    }

    public function dosenTeamFor(EdomResponse $response): string
    {
        return $this->aggregator->dosenTeamFor($response);
    }

    public function sectionMissingFor(EdomResponse $response): bool
    {
        return $this->aggregator->sectionMissingFor($response);
    }

    public function formattedAverageScoreFor(EdomResponse $response): string
    {
        $average = $this->averageScoreFor($response);

        return $average === null ? '-' : number_format($average, 2, ',', '.');
    }

    public function averageScoreFor(EdomResponse $response): ?float
    {
        $details = $response->relationLoaded('details')
            ? $response->details
            : $response->details()->with('questionOption')->get();

        $average = $details
            ->map(fn ($detail) => $detail->option_score_for_display)
            ->filter(fn ($score): bool => $score !== null && $score !== '')
            ->avg();

        return $average === null ? null : round((float) $average, 2);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function sectionFor(EdomResponse $response): ?array
    {
        $year = $response->getAttribute('siakad_idtahunajaran') ?? $response->period?->year;
        $semester = $response->getAttribute('siakad_idsemester') ?? $response->period?->siakad_idsemester;

        if ($year === null || $year === '' || $semester === null || $semester === '') {
            return null;
        }

        return $this->aggregator->sectionFor(
            $year,
            $semester,
            $response->siakad_idtawarmatakuliahdetail,
            $response->siakad_idmatakuliah,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function studentProfileFor(EdomResponse $response): array
    {
        $studentId = trim((string) $response->siakad_idmahasiswa);

        if ($studentId === '') {
            return [];
        }

        if (! array_key_exists($studentId, $this->studentProfiles)) {
            try {
                $this->studentProfiles[$studentId] = Cache::remember(
                    'edom:student-profile:'.$studentId,
                    now()->addHours(6),
                    function () use ($studentId): array {
                        $profile = collect($this->siakad->mahasiswa([$studentId]))
                            ->filter(fn ($profile): bool => is_array($profile))
                            ->first(fn (array $profile): bool => (string) data_get($profile, 'siakad_idmahasiswa') === $studentId);

                        return is_array($profile) ? $profile : [];
                    }
                );
            } catch (Throwable $exception) {
                report($exception);

                $this->studentProfiles[$studentId] = [];
            }
        }

        return $this->studentProfiles[$studentId];
    }

    private function semesterIdFor(EdomResponse $response): int|string|null
    {
        $semester = $response->getAttribute('siakad_idsemester') ?? $response->period?->siakad_idsemester;

        return $semester === null || $semester === '' ? null : $semester;
    }

    private function studentGroupKey(EdomResponse $response): string
    {
        return implode(':', [
            (string) $response->siakad_idmahasiswa,
            (string) $response->edom_period_id,
            (string) $response->edom_setting_id,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function semesterLabels(): array
    {
        if ($this->semesterLabels !== null) {
            return $this->semesterLabels;
        }

        try {
            $this->semesterLabels = Cache::remember('edom:semester-labels', now()->addDay(), function (): array {
                return collect($this->siakad->semester())
                    ->filter(fn ($semester): bool => is_array($semester) && data_get($semester, 'id') !== null)
                    ->mapWithKeys(function (array $semester): array {
                        $id = (string) data_get($semester, 'id');
                        $label = trim((string) (
                            data_get($semester, 'nama')
                            ?? data_get($semester, 'name')
                            ?? data_get($semester, 'semester')
                            ?? data_get($semester, 'label')
                            ?? ''
                        ));

                        return $id === '' ? [] : [$id => $label];
                    })
                    ->all();
            });
        } catch (Throwable $exception) {
            report($exception);

            $this->semesterLabels = [];
        }

        return $this->semesterLabels;
    }
}
