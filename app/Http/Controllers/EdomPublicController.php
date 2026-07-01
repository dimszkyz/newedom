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

        $activeEdoms = EdomSettings::query()
            ->with(['programStudis'])
            ->withCount(['categories', 'questions'])
            ->where('status', 'active')
            ->latest('id')
            ->get();

        $studentEdomSections = collect();

        if ($student && ! $studentFetchError) {
            $completedSectionKeys = $this->completedSectionKeys(
                $student,
                $activeEdoms->pluck('id')->map(fn ($id) => (int) $id)->all()
            );

            $studentEdomSections = $activeEdoms
                ->map(function (EdomSettings $edom) use ($studentSections, $completedSectionKeys): array {
                    $sections = collect($this->sectionsForEdomSettings($edom, $studentSections))
                        ->map(function (array $section) use ($edom, $completedSectionKeys): array {
                            $completionKey = $this->sectionCompletionKey($edom->id, $section);

                            return [
                                'section' => $section,
                                'section_key' => $this->sectionRouteKey($section),
                                'completed' => $completionKey !== null
                                    && isset($completedSectionKeys[$completionKey]),
                            ];
                        })
                        ->values();

                    return [
                        'edom' => $edom,
                        'sections' => $sections,
                    ];
                })
                ->filter(fn (array $group): bool => $group['sections']->isNotEmpty())
                ->values();
        }

        $closedEdoms = EdomSettings::query()
            ->with(['programStudis'])
            ->where('status', 'closed')
            ->latest('id')
            ->limit(6)
            ->get();

        $draftCount = EdomSettings::query()
            ->where('status', 'draft')
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
        $sections = [];

        if (! is_array($student)) {
            return view('edom.status', [
                'edom' => $edom,
                'statusTitle' => 'Sesi SIAKAD tidak ditemukan',
                'statusMessage' => 'Buka pengisian EDOM melalui tombol Isi EDOM pada halaman SIAKAD.',
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
            return redirect()
                ->route('edom.home')
                ->with('error', 'Pengisian EDOM harus dibuka melalui SIAKAD.');
        }

        return $this->submitStudentSections($request, $edom);
    }

    private function submitStudentSections(Request $request, Model $edom): RedirectResponse
    {
        $student = session('edom_student');
        $questions = $edom->categories->flatMap(fn ($category) => $category->questions);
        $optionIds = $edom->questionOptions->pluck('id')->map(fn ($id) => (string) $id)->values()->all();

        $request->validate([
            'sections' => ['required', 'array', 'size:1'],
        ]);

        try {
            $currentSections = $this->fetchStudentSections($student);
            $settingSections = $this->sectionsForEdomSettings($edom, $currentSections);
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Jawaban belum disimpan karena data KRS terbaru tidak dapat diverifikasi.');
        }

        $submittedSections = $this->resolveSubmittedSections(
            $request->input('sections', []),
            $settingSections
        );

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

        DB::transaction(function () use ($request, $edom, $questions, $submittedSections, $student) {
            $period = $this->firstOrCreatePeriod($student);

            foreach ($submittedSections as $sectionKey => $section) {
                $response = EdomResponse::updateOrCreate(
                    [
                        'edom_period_id' => $period->id,
                        'edom_setting_id' => $edom->id,
                        'siakad_idmahasiswa' => (string) $student['siakad_idmahasiswa'],
                        'siakad_idmatakuliah' => $this->nullableInteger($section['idmatakuliah'] ?? null),
                        'siakad_idtawarmatakuliahdetail' => $this->persistedSectionDetailId($section),
                    ],
                    [
                        'submitted_at' => now(),
                    ]
                );

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

                    $optionId = (int) $request->input("answers.{$sectionKey}.{$question->id}");

                    EdomResponseDetail::updateOrCreate(
                        [
                            'edom_response_id' => $response->id,
                            'edom_question_id' => $question->id,
                        ],
                        [
                            'edom_option_id' => $optionId,
                            'answer_text' => null,
                        ]
                    );
                }
            }
        });

        try {
            $currentSections = $this->fetchStudentSections($student);

            if ($this->studentHasCompletedAllApplicableEdoms($student, $currentSections)) {
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
        $sections = app(UnwApiSiakad::class)->krs(
            $student['siakad_idmahasiswa'],
            $student['siakad_idtahunajaran'],
            $student['siakad_idsemester']
        );

        return collect($sections)->filter(fn ($section) => is_array($section))->values()->all();
    }

    private function fetchStudentSemester(array $student): ?array
    {
        $semesterId = (string) $student['siakad_idsemester'];
        $semesters = app(UnwApiSiakad::class)->semester();

        $semester = collect($semesters)->first(function (mixed $semester) use ($semesterId): bool {
            return is_array($semester)
                && (string) ($semester['id'] ?? '') === $semesterId;
        });

        return is_array($semester) ? $semester : null;
    }

    private function fetchStudentProfile(array $student): ?array
    {
        $studentId = (string) $student['siakad_idmahasiswa'];
        $profiles = app(UnwApiSiakad::class)->mahasiswa([$studentId]);

        $profile = collect($profiles)->first(function (mixed $profile) use ($studentId): bool {
            return is_array($profile)
                && (string) ($profile['siakad_idmahasiswa'] ?? '') === $studentId;
        });

        return is_array($profile) ? $profile : null;
    }

    private function sectionsForEdomSettings(Model $edom, array $sections): array
    {
        return array_values($sections);
    }

    private function resolveSubmittedSections(array $submittedSections, array $authoritativeSections): array
    {
        $authoritativeBySectionKey = collect($authoritativeSections)
            ->filter(function (array $section): bool {
                return $this->sectionRouteKey($section) !== null
                    && $this->nullableInteger($section['idmatakuliah'] ?? null) !== null;
            })
            ->keyBy(fn (array $section): string => (string) $this->sectionRouteKey($section));

        if (
            $authoritativeBySectionKey->count() !== count($authoritativeSections)
            || count($submittedSections) !== 1
        ) {
            $this->throwInvalidSections();
        }

        $resolvedSections = [];
        $seenSectionKeys = [];

        foreach ($submittedSections as $sectionKey => $submittedSection) {
            if (
                ! is_string($sectionKey)
                || ! preg_match('/^s_\d+_[A-Za-z0-9_-]+$/', $sectionKey)
                || ! is_array($submittedSection)
            ) {
                $this->throwInvalidSections();
            }

            $submittedRouteKey = $this->sectionRouteKey($submittedSection);
            $courseId = $this->nullableInteger($submittedSection['idmatakuliah'] ?? null);
            $authoritativeSection = $submittedRouteKey === null
                ? null
                : $authoritativeBySectionKey->get($submittedRouteKey);

            if (
                ! is_array($authoritativeSection)
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

        $period = EdomPeriod::query()
            ->where('year', (int) $student['siakad_idtahunajaran'])
            ->where('siakad_idsemester', (int) $student['siakad_idsemester'])
            ->first();

        if (! $period) {
            return [];
        }

        return EdomResponse::query()
            ->where('edom_period_id', $period->id)
            ->where('siakad_idmahasiswa', (string) $student['siakad_idmahasiswa'])
            ->whereIn('edom_setting_id', $edomSettingIds)
            ->get([
                'edom_setting_id',
                'siakad_idmatakuliah',
                'siakad_idtawarmatakuliahdetail',
            ])
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

    private function sectionCompletionKey(int $edomSettingId, array $section): ?string
    {
        $routeKey = $this->sectionRouteKey($section);
        $courseId = $this->nullableInteger($section['idmatakuliah'] ?? null);

        if ($routeKey === null || $courseId === null) {
            return null;
        }

        return $edomSettingId.':'.$routeKey.':'.$courseId;
    }

    private function sectionRouteKey(array $section): ?string
    {
        $detailId = $this->nullableInteger($section['idtawarmatakuliahdetail'] ?? null);

        if ($detailId !== null) {
            return 'd_'.$detailId;
        }

        $courseId = $this->nullableInteger($section['idmatakuliah'] ?? null);

        if ($courseId !== null) {
            return 'm_'.$courseId;
        }

        return null;
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

        $applicableEdoms = EdomSettings::query()
            ->with('programStudis')
            ->where('status', 'active')
            ->get();

        if ($applicableEdoms->isEmpty()) {
            return false;
        }

        foreach ($applicableEdoms as $edom) {
            $settingSections = $this->sectionsForEdomSettings($edom, $sections);

            if (
                $settingSections === []
                || ! $this->studentHasCompletedAllSections($student, $settingSections, $edom)
            ) {
                return false;
            }
        }

        return true;
    }

    private function studentHasCompletedAllSections(array $student, array $sections, Model $edom): bool
    {
        if ($sections === []) {
            return false;
        }

        $period = EdomPeriod::query()
            ->where('year', (int) $student['siakad_idtahunajaran'])
            ->where('siakad_idsemester', (int) $student['siakad_idsemester'])
            ->first();

        if (! $period) {
            return false;
        }

        $completedSections = EdomResponse::query()
            ->where('edom_period_id', $period->id)
            ->where('edom_setting_id', $edom->id)
            ->where('siakad_idmahasiswa', (string) $student['siakad_idmahasiswa'])
            ->get([
                'siakad_idmatakuliah',
                'siakad_idtawarmatakuliahdetail',
            ])
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

    private function firstOrCreatePeriod(array $student): EdomPeriod
    {
        return EdomPeriod::firstOrCreate([
            'year' => (int) $student['siakad_idtahunajaran'],
            'siakad_idsemester' => (int) $student['siakad_idsemester'],
        ]);
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
