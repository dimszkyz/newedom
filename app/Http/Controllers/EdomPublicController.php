<?php

namespace App\Http\Controllers;

use App\Models\EdomPeriod;
use App\Models\EdomQuestion;
use App\Models\EdomResponse;
use App\Models\EdomResponseDetail;
use App\Models\EdomSettings;
use App\Services\Siakad\UnwApiSiakad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class EdomPublicController extends Controller
{
    private const RESPONSE_UPDATE_LOCKED_MESSAGE = 'Pembaruan jawaban pada periode EDOM ini dikunci. Jawaban yang sudah tersimpan tidak dapat diperbarui, tetapi mata kuliah yang belum diisi masih dapat dikerjakan.';

    private const PERIOD_UNAVAILABLE_MESSAGE = 'Periode EDOM untuk tahun ajaran dan semester mahasiswa belum dibuka di SIAKAD.';

    public function index(Request $request): View
    {
        $student = session('edom_student');
        $studentSemester = null;
        $studentSemesterFetchError = null;
        $studentSections = [];
        $studentFetchError = null;
        $studentProfile = null;
        $studentProfileFetchError = null;

        if ($student) {
            try {
                $studentSemester = $this->fetchStudentSemester($student);

                if ($studentSemester === null) {
                    $studentSemesterFetchError = 'Data semester tidak ditemukan pada SIAKAD.';
                }
            } catch (Throwable $exception) {
                report($exception);
                $studentSemesterFetchError = 'Gagal mengambil data semester dari SIAKAD.';
            }

            try {
                $studentSections = $this->fetchStudentSections($student);
            } catch (Throwable $exception) {
                report($exception);
                $studentFetchError = 'Gagal mengambil data KRS dari SIAKAD. Periksa konfigurasi API SIAKAD atau coba beberapa saat lagi.';
            }

            try {
                $studentProfile = $this->fetchStudentProfile($student);

                if ($studentProfile === null) {
                    $studentProfileFetchError = 'Data mahasiswa tidak ditemukan pada SIAKAD.';
                }
            } catch (Throwable $exception) {
                report($exception);
                $studentProfileFetchError = 'Gagal mengambil data mahasiswa dari SIAKAD.';
            }
        }

        $studentPeriod = is_array($student) ? $this->studentPeriod($student) : null;

        $activeEdoms = EdomSettings::query()
            ->with(['programStudis'])
            ->withCount(['categories', 'questions'])
            ->where('status', EdomSettings::STATUS_ACTIVE)
            ->when(is_array($student), fn ($query) => $studentPeriod?->isOpenInSiakad()
                ? $query
                : $query->whereRaw('1 = 0'))
            ->latest('id')
            ->get();

        $studentEdomSections = collect();

        if ($student && ! $studentFetchError) {
            $allowsResponseUpdates = $studentPeriod?->allowsResponseUpdates() ?? false;
            $completedSectionKeys = $this->completedSectionKeys(
                $student,
                $activeEdoms->pluck('id')->map(fn ($id) => (int) $id)->all()
            );

            $studentEdomSections = $activeEdoms
                ->map(function (EdomSettings $edom) use ($studentSections, $completedSectionKeys, $allowsResponseUpdates, $studentPeriod): array {
                    $sections = collect($this->sectionsForEdomSettings($edom, $studentSections))
                        ->map(function (array $section) use ($edom, $completedSectionKeys, $allowsResponseUpdates): array {
                            $completionKey = $this->sectionCompletionKey($edom->id, $section);
                            $completed = $completionKey !== null && isset($completedSectionKeys[$completionKey]);

                            return [
                                'section' => $section,
                                'section_key' => $this->sectionRouteKey($section),
                                'completed' => $completed,
                                'update_locked' => $completed && ! $allowsResponseUpdates,
                            ];
                        })
                        ->values();

                    return [
                        'edom' => $edom,
                        'sections' => $sections,
                        'period_status' => $studentPeriod?->lifecycle_status ?? 'Tidak Tersedia',
                    ];
                })
                ->filter(fn (array $group): bool => $group['sections']->isNotEmpty())
                ->values();
        }

        $closedEdoms = EdomSettings::query()
            ->with(['programStudis'])
            ->where('status', EdomSettings::STATUS_CLOSED)
            ->latest('id')
            ->limit(6)
            ->get();

        $draftCount = EdomSettings::query()
            ->where('status', EdomSettings::STATUS_DRAFT)
            ->count();

        return view('edom.index', [
            'activeEdoms' => $activeEdoms,
            'closedEdoms' => $closedEdoms,
            'draftCount' => $draftCount,
            'student' => $student,
            'studentSemester' => $studentSemester,
            'studentSemesterFetchError' => $studentSemesterFetchError,
            'studentProfile' => $studentProfile,
            'studentProfileFetchError' => $studentProfileFetchError,
            'studentSections' => $studentSections,
            'studentEdomSections' => $studentEdomSections,
            'studentFetchError' => $studentFetchError,
        ]);
    }

    public function enter(Request $request): RedirectResponse
    {
        $payload = $this->verifyHandoffToken((string) $request->query('token'));

        session([
            'edom_student' => [
                'siakad_idmahasiswa' => (string) $payload['siakad_idmahasiswa'],
                'siakad_idtahunajaran' => (int) $payload['siakad_idtahunajaran'],
                'siakad_idsemester' => (int) $payload['siakad_idsemester'],
                'return_url' => $payload['return_url'] ?? null,
            ],
        ]);

        return redirect()->route('edom.home');
    }

    public function show(Request $request, mixed $edom): View
    {
        $edom = $this->prepareEdomSettings($edom);

        if (! $edom->isActive()) {
            return view('edom.status', [
                'edom' => $edom,
                'statusTitle' => $edom->isDraft() ? 'EDOM belum dibuka' : 'EDOM sudah ditutup',
                'statusMessage' => $edom->isDraft()
                    ? 'Form evaluasi ini masih berstatus draft, sehingga belum bisa diisi oleh mahasiswa.'
                    : 'Form evaluasi ini sudah ditutup dan tidak lagi menerima jawaban baru.',
            ]);
        }

        $student = session('edom_student');

        if (! is_array($student)) {
            return view('edom.status', [
                'edom' => $edom,
                'statusTitle' => 'Sesi SIAKAD tidak ditemukan',
                'statusMessage' => 'Buka pengisian EDOM melalui tombol Isi EDOM pada halaman SIAKAD.',
            ]);
        }

        $period = $this->studentPeriod($student);

        if (! $period || ! $period->isOpenInSiakad()) {
            return view('edom.status', [
                'edom' => $edom,
                'statusBadge' => $period?->lifecycle_status ?? 'Tidak Tersedia',
                'statusTitle' => 'Periode EDOM belum dibuka',
                'statusMessage' => $period ? $this->periodUnavailableMessage($period) : self::PERIOD_UNAVAILABLE_MESSAGE,
            ]);
        }

        try {
            $sections = $this->sectionsForEdomSettings($edom, $this->fetchStudentSections($student));
        } catch (Throwable $exception) {
            report($exception);

            return view('edom.status', [
                'edom' => $edom,
                'statusTitle' => 'Data KRS tidak dapat dimuat',
                'statusMessage' => 'Aplikasi EDOM gagal mengambil daftar mata kuliah dari SIAKAD. Periksa konfigurasi API atau coba beberapa saat lagi.',
            ]);
        }

        $selectedSectionKey = trim((string) $request->query('section', ''));

        if ($selectedSectionKey === '') {
            return view('edom.status', [
                'edom' => $edom,
                'statusTitle' => 'Pilih mata kuliah dari daftar KRS',
                'statusMessage' => 'Kembali ke halaman EDOM, lalu pilih satu mata kuliah yang akan dievaluasi.',
            ]);
        }

        $sections = collect($sections)
            ->filter(fn (array $section): bool => $this->sectionRouteKey($section) === $selectedSectionKey)
            ->values()
            ->all();

        if ($sections === []) {
            return view('edom.status', [
                'edom' => $edom,
                'statusTitle' => 'Mata kuliah tidak ditemukan',
                'statusMessage' => 'Mata kuliah yang dipilih tidak ada pada KRS terbaru atau tidak cocok dengan EdomSettings ini.',
            ]);
        }

        $sectionCompleted = $this->studentHasCompletedSection($student, $edom, $sections[0]);

        if ($sectionCompleted && $period->locksResponseUpdates()) {
            return view('edom.status', [
                'edom' => $edom,
                'statusTitle' => 'Jawaban EDOM terkunci',
                'statusMessage' => self::RESPONSE_UPDATE_LOCKED_MESSAGE,
            ]);
        }

        return view('edom.show', compact('edom', 'student', 'sections'));
    }

    public function submitFromHome(Request $request): RedirectResponse
    {
        return $this->submit($request, $request->input('edom_id'));
    }

    public function submit(Request $request, mixed $edom): RedirectResponse
    {
        $edom = $this->prepareEdomSettings($edom);

        if (! $edom->isActive()) {
            return redirect()->route('edom.home')->with('error', 'EDOM tidak sedang aktif.');
        }

        if (! is_array(session('edom_student'))) {
            return redirect()->route('edom.home')->with('error', 'Pengisian EDOM harus dibuka melalui SIAKAD.');
        }

        $student = session('edom_student');
        $period = $this->studentPeriod($student);

        if (! $period || ! $period->isOpenInSiakad()) {
            return redirect()
                ->route('edom.home')
                ->with('error', $period ? $this->periodUnavailableMessage($period) : self::PERIOD_UNAVAILABLE_MESSAGE);
        }

        return $this->submitStudentSections($request, $edom, $period);
    }

    private function submitStudentSections(Request $request, Model $edom, EdomPeriod $period): RedirectResponse
    {
        $student = session('edom_student');
        $questions = $edom->categories->flatMap(fn ($category) => $category->questions);
        $optionIds = $edom->questionOptions->pluck('id')->map(fn ($id) => (string) $id)->values()->all();

        $request->validate(['sections' => ['required', 'array', 'size:1']]);

        try {
            $settingSections = $this->sectionsForEdomSettings($edom, $this->fetchStudentSections($student));
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Jawaban belum disimpan karena data KRS terbaru tidak dapat diverifikasi.');
        }

        $submittedSections = $this->resolveSubmittedSections($request->input('sections', []), $settingSections);

        if (
            $period->locksResponseUpdates()
            && collect($submittedSections)->contains(fn (array $section): bool => $this->studentHasCompletedSection($student, $edom, $section))
        ) {
            return redirect()->route('edom.home')->with('error', self::RESPONSE_UPDATE_LOCKED_MESSAGE);
        }

        $rules = [];

        foreach ($submittedSections as $sectionKey => $section) {
            foreach ($questions as $question) {
                if ($this->isTextQuestion($question)) {
                    $rules["essays.{$sectionKey}.{$question->id}"] = ['nullable', 'string', 'max:5000'];
                } else {
                    $rules["answers.{$sectionKey}.{$question->id}"] = ['required', Rule::in($optionIds)];
                }
            }
        }

        $request->validate($rules);

        DB::transaction(function () use ($request, $edom, $questions, $submittedSections, $student, $period): void {
            foreach ($submittedSections as $sectionKey => $section) {
                $responseIdentity = [
                    'edom_period_id' => $period->id,
                    'edom_setting_id' => $edom->id,
                    'siakad_idmahasiswa' => (string) $student['siakad_idmahasiswa'],
                    'siakad_idmatakuliah' => $this->nullableInteger($section['idmatakuliah'] ?? null),
                    'siakad_idtawarmatakuliahdetail' => $this->persistedSectionDetailId($section),
                ];

                $currentPeriod = $period->fresh();

                if (! $currentPeriod->isOpenInSiakad()) {
                    throw ValidationException::withMessages([
                        'sections' => $this->periodUnavailableMessage($currentPeriod),
                    ]);
                }

                if ($currentPeriod->locksResponseUpdates() && EdomResponse::query()->where($responseIdentity)->exists()) {
                    throw ValidationException::withMessages([
                        'sections' => self::RESPONSE_UPDATE_LOCKED_MESSAGE,
                    ]);
                }

                $responseValues = ['submitted_at' => now()];
                $programStudiId = $this->nullableInteger($section['id_unw_program_studi'] ?? null);

                if ($programStudiId !== null) {
                    $responseValues['id_unw_program_studi'] = $programStudiId;
                }

                $response = EdomResponse::updateOrCreate($responseIdentity, $responseValues);

                foreach ($questions as $question) {
                    if ($this->isTextQuestion($question)) {
                        EdomResponseDetail::updateOrCreate(
                            [
                                'edom_response_id' => $response->id,
                                'edom_question_id' => $question->id,
                            ],
                            [
                                'edom_option_id' => null,
                                'answer_text' => $request->input("essays.{$sectionKey}.{$question->id}"),
                            ]
                        );

                        continue;
                    }

                    EdomResponseDetail::updateOrCreate(
                        [
                            'edom_response_id' => $response->id,
                            'edom_question_id' => $question->id,
                        ],
                        [
                            'edom_option_id' => (int) $request->input("answers.{$sectionKey}.{$question->id}"),
                            'answer_text' => null,
                        ]
                    );
                }
            }
        });

        try {
            if ($this->studentHasCompletedAllApplicableEdoms($student, $this->fetchStudentSections($student))) {
                app(UnwApiSiakad::class)->complete(
                    $student['siakad_idmahasiswa'],
                    $student['siakad_idtahunajaran'],
                    $student['siakad_idsemester']
                );

                return redirect()->away($student['return_url'] ?: config('edom.siakad_fallback_url'));
            }
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('edom.home')
                ->with('error', 'Jawaban tersimpan, tetapi status selesai belum dapat dikirim ke SIAKAD.');
        }

        return redirect()->route('edom.home')->with('success', 'Jawaban EDOM untuk mata kuliah berhasil disimpan.');
    }

    private function prepareEdomSettings(mixed $edom): Model
    {
        $edom = $edom instanceof Model ? $edom : EdomSettings::query()->findOrFail($edom);

        $edom->load([
            'programStudis',
            'categories' => fn ($query) => $query->orderBy('id'),
            'categories.questions' => fn ($query) => $query->orderBy('id'),
            'questionOptions' => fn ($query) => $query->orderBy('score')->orderBy('id'),
        ]);

        return $edom;
    }

    private function fetchStudentSections(array $student): array
    {
        return collect(app(UnwApiSiakad::class)->krs(
            $student['siakad_idmahasiswa'],
            $student['siakad_idtahunajaran'],
            $student['siakad_idsemester']
        ))->filter(fn ($section) => is_array($section))->values()->all();
    }

    private function fetchStudentSemester(array $student): ?array
    {
        $semesterId = (string) $student['siakad_idsemester'];
        $semester = collect(app(UnwApiSiakad::class)->semester())->first(
            fn (mixed $semester): bool => is_array($semester) && (string) ($semester['id'] ?? '') === $semesterId
        );

        return is_array($semester) ? $semester : null;
    }

    private function fetchStudentProfile(array $student): ?array
    {
        $studentId = (string) $student['siakad_idmahasiswa'];
        $profile = collect(app(UnwApiSiakad::class)->mahasiswa([$studentId]))->first(
            fn (mixed $profile): bool => is_array($profile) && (string) ($profile['siakad_idmahasiswa'] ?? '') === $studentId
        );

        return is_array($profile) ? $profile : null;
    }

    private function sectionsForEdomSettings(Model $edom, array $sections): array
    {
        $programStudiIds = $edom->programStudis
            ->pluck('id_unw_program_studi')
            ->filter(fn ($id): bool => $id !== null)
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($programStudiIds === []) {
            return [];
        }

        return collect($sections)
            ->filter(fn (array $section): bool => in_array((int) data_get($section, 'id_unw_program_studi'), $programStudiIds, true))
            ->values()
            ->all();
    }

    private function resolveSubmittedSections(array $submittedSections, array $authoritativeSections): array
    {
        $authoritativeBySectionKey = collect($authoritativeSections)
            ->filter(fn (array $section): bool => $this->sectionRouteKey($section) !== null
                && $this->nullableInteger($section['idmatakuliah'] ?? null) !== null)
            ->keyBy(fn (array $section): string => (string) $this->sectionRouteKey($section));

        if ($authoritativeBySectionKey->count() !== count($authoritativeSections) || count($submittedSections) !== 1) {
            $this->throwInvalidSections();
        }

        $resolvedSections = [];
        $seenSectionKeys = [];

        foreach ($submittedSections as $sectionKey => $submittedSection) {
            if (! is_string($sectionKey) || ! preg_match('/^s_\d+_[A-Za-z0-9_-]+$/', $sectionKey) || ! is_array($submittedSection)) {
                $this->throwInvalidSections();
            }

            $submittedRouteKey = $this->sectionRouteKey($submittedSection);
            $courseId = $this->nullableInteger($submittedSection['idmatakuliah'] ?? null);
            $authoritativeSection = $submittedRouteKey === null ? null : $authoritativeBySectionKey->get($submittedRouteKey);

            if (! is_array($authoritativeSection)
                || $courseId === null
                || $courseId !== $this->nullableInteger($authoritativeSection['idmatakuliah'] ?? null)
                || in_array($submittedRouteKey, $seenSectionKeys, true)
            ) {
                $this->throwInvalidSections();
            }

            $seenSectionKeys[] = $submittedRouteKey;
            $resolvedSections[$sectionKey] = $authoritativeSection;
        }

        return $resolvedSections;
    }

    private function throwInvalidSections(): never
    {
        throw ValidationException::withMessages([
            'sections' => 'Daftar mata kuliah berubah atau tidak sesuai dengan KRS terbaru. Muat ulang halaman lalu coba lagi.',
        ]);
    }

    private function completedSectionKeys(array $student, array $edomSettingIds): array
    {
        if ($edomSettingIds === []) {
            return [];
        }

        $period = $this->studentPeriod($student);

        if (! $period) {
            return [];
        }

        return EdomResponse::query()
            ->where('edom_period_id', $period->id)
            ->where('siakad_idmahasiswa', (string) $student['siakad_idmahasiswa'])
            ->whereIn('edom_setting_id', $edomSettingIds)
            ->get(['edom_setting_id', 'siakad_idmatakuliah', 'siakad_idtawarmatakuliahdetail'])
            ->flatMap(function (EdomResponse $response): array {
                $keys = [];
                $detailKey = $this->sectionCompletionKey($response->edom_setting_id, [
                    'idmatakuliah' => $response->siakad_idmatakuliah,
                    'idtawarmatakuliahdetail' => $response->siakad_idtawarmatakuliahdetail,
                ]);
                $courseKey = $this->sectionCompletionKey($response->edom_setting_id, [
                    'idmatakuliah' => $response->siakad_idmatakuliah,
                ]);

                if ($detailKey !== null) {
                    $keys[$detailKey] = true;
                }

                if ($courseKey !== null) {
                    $keys[$courseKey] = true;
                }

                return $keys;
            })
            ->all();
    }

    private function studentHasCompletedSection(array $student, Model $edom, array $section): bool
    {
        $completionKey = $this->sectionCompletionKey((int) $edom->id, $section);

        return $completionKey !== null && isset($this->completedSectionKeys($student, [(int) $edom->id])[$completionKey]);
    }

    private function studentPeriod(array $student): ?EdomPeriod
    {
        return EdomPeriod::query()
            ->where('year', (int) $student['siakad_idtahunajaran'])
            ->where('siakad_idsemester', (int) $student['siakad_idsemester'])
            ->first();
    }

    private function sectionCompletionKey(int $edomSettingId, array $section): ?string
    {
        $routeKey = $this->sectionRouteKey($section);
        $courseId = $this->nullableInteger($section['idmatakuliah'] ?? null);

        return $routeKey === null || $courseId === null ? null : $edomSettingId.':'.$routeKey.':'.$courseId;
    }

    private function sectionRouteKey(array $section): ?string
    {
        $detailId = $this->nullableInteger($section['idtawarmatakuliahdetail'] ?? null);

        if ($detailId !== null) {
            return 'd_'.$detailId;
        }

        $courseId = $this->nullableInteger($section['idmatakuliah'] ?? null);

        return $courseId !== null ? 'm_'.$courseId : null;
    }

    private function persistedSectionDetailId(array $section): ?int
    {
        return $this->nullableInteger($section['idtawarmatakuliahdetail'] ?? null)
            ?? $this->nullableInteger($section['idmatakuliah'] ?? null);
    }

    private function studentHasCompletedAllApplicableEdoms(array $student, array $sections): bool
    {
        if ($sections === []) {
            return false;
        }

        $period = $this->studentPeriod($student);

        if (! $period || ! $period->isOpenInSiakad()) {
            return false;
        }

        $applicableEdoms = EdomSettings::query()
            ->with('programStudis')
            ->where('status', EdomSettings::STATUS_ACTIVE)
            ->get()
            ->map(fn (EdomSettings $edom): array => [
                'edom' => $edom,
                'sections' => $this->sectionsForEdomSettings($edom, $sections),
            ])
            ->filter(fn (array $item): bool => $item['sections'] !== [])
            ->values();

        if ($applicableEdoms->isEmpty()) {
            return false;
        }

        foreach ($applicableEdoms as $item) {
            if (! $this->studentHasCompletedAllSections($student, $item['sections'], $item['edom'])) {
                return false;
            }
        }

        return true;
    }

    private function periodUnavailableMessage(EdomPeriod $period): string
    {
        return $period->isOpenInSiakad()
            ? 'Periode EDOM sedang terbuka.'
            : self::PERIOD_UNAVAILABLE_MESSAGE;
    }

    private function studentHasCompletedAllSections(array $student, array $sections, Model $edom): bool
    {
        if ($sections === []) {
            return false;
        }

        $period = $this->studentPeriod($student);

        if (! $period) {
            return false;
        }

        $completedSections = EdomResponse::query()
            ->where('edom_period_id', $period->id)
            ->where('edom_setting_id', $edom->id)
            ->where('siakad_idmahasiswa', (string) $student['siakad_idmahasiswa'])
            ->get(['siakad_idmatakuliah', 'siakad_idtawarmatakuliahdetail'])
            ->flatMap(function (EdomResponse $response) use ($edom): array {
                $keys = [];
                $detailKey = $this->sectionCompletionKey($edom->id, [
                    'idmatakuliah' => $response->siakad_idmatakuliah,
                    'idtawarmatakuliahdetail' => $response->siakad_idtawarmatakuliahdetail,
                ]);
                $courseKey = $this->sectionCompletionKey($edom->id, [
                    'idmatakuliah' => $response->siakad_idmatakuliah,
                ]);

                if ($detailKey !== null) {
                    $keys[$detailKey] = true;
                }

                if ($courseKey !== null) {
                    $keys[$courseKey] = true;
                }

                return $keys;
            });

        foreach ($sections as $section) {
            $completionKey = $this->sectionCompletionKey($edom->id, $section);

            if ($completionKey === null || ! $completedSections->has($completionKey)) {
                return false;
            }
        }

        return true;
    }

    private function verifyHandoffToken(string $token): array
    {
        [$b64, $signature] = array_pad(explode('.', $token, 2), 2, '');
        $secret = (string) config('edom.hmac_siakad_secret');

        abort_unless($token !== '' && $b64 !== '' && $signature !== '' && $secret !== '', 401, 'Invalid token');
        $expected = hash_hmac('sha256', $b64, $secret);
        abort_unless(hash_equals($expected, $signature), 401, 'Invalid token');
        $decoded = $this->base64UrlDecode($b64);
        abort_unless($decoded !== false, 400, 'Invalid token payload');
        $payload = json_decode($decoded, true);
        abort_unless(is_array($payload), 400, 'Invalid token payload');
        abort_if((int) ($payload['exp'] ?? 0) < time(), 401, 'Token expired');

        foreach (['siakad_idmahasiswa', 'siakad_idtahunajaran', 'siakad_idsemester'] as $key) {
            abort_unless(array_key_exists($key, $payload), 400, "Missing {$key} in token payload");
        }

        return $payload;
    }

    private function base64UrlDecode(string $value): string|false
    {
        $base64 = strtr($value, '-_', '+/');
        $padding = strlen($base64) % 4;

        if ($padding > 0) {
            $base64 .= str_repeat('=', 4 - $padding);
        }

        return base64_decode($base64, true);
    }

    private function nullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function isTextQuestion(EdomQuestion $question): bool
    {
        return in_array(strtolower((string) $question->question_type), ['text', 'essay', 'esai', 'uraian', 'textarea'], true);
    }
}
