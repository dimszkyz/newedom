<?php

namespace App\Http\Controllers;

use App\Models\EdomPeriod;
use App\Models\EdomQuestion;
use App\Models\EdomResponse;
use App\Models\EdomResponseDetail;
use App\Models\SettingEdom;
use App\Services\Siakad\UnwApiSiakad;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class EdomPublicController extends Controller
{
    public function index(Request $request): View
    {
        $student = session('edom_student');
        $studentSections = [];
        $studentFetchError = null;
        $studentProgramStudiIds = [];

        if ($student) {
            try {
                $studentSections = $this->fetchStudentSections($student);
                $studentProgramStudiIds = $this->programStudiIdsFromSections($studentSections);
            } catch (Throwable $exception) {
                report($exception);
                $studentFetchError = 'Gagal mengambil data KRS dari SIAKAD. Periksa konfigurasi API SIAKAD atau coba beberapa saat lagi.';
            }
        }

        $activeQuery = SettingEdom::query()
            ->with(['programStudis'])
            ->withCount(['categories', 'questions'])
            ->where('status', 'active')
            ->latest('id');

        if ($student && $studentProgramStudiIds !== []) {
            $this->scopeSettingEdomsForProgramStudi($activeQuery, $studentProgramStudiIds);
        }

        $activeEdoms = $activeQuery->get();
        $selectedEdomId = (int) $request->query('edom');

        if ($selectedEdomId > 0) {
            $selectedEdom = $activeEdoms->firstWhere('id', $selectedEdomId);

            if ($selectedEdom) {
                return $this->show($selectedEdom);
            }
        }

        if (! $studentFetchError && $activeEdoms->count() === 1) {
            return $this->show($activeEdoms->first());
        }

        $closedEdoms = SettingEdom::query()
            ->with(['programStudis'])
            ->where('status', 'closed')
            ->latest('id')
            ->limit(6)
            ->get();

        $draftCount = SettingEdom::query()
            ->where('status', 'draft')
            ->count();

        return view('edom.index', [
            'activeEdoms' => $activeEdoms,
            'closedEdoms' => $closedEdoms,
            'draftCount' => $draftCount,
            'student' => $student,
            'studentSections' => $studentSections,
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

    public function show(mixed $edom): View
    {
        $edom = $this->prepareSettingEdom($edom);

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

        if ($student) {
            try {
                $sections = $this->sectionsForSettingEdom($edom, $this->fetchStudentSections($student));
            } catch (Throwable $exception) {
                report($exception);

                return view('edom.status', [
                    'edom' => $edom,
                    'statusTitle' => 'Data KRS tidak dapat dimuat',
                    'statusMessage' => 'Aplikasi EDOM gagal mengambil daftar mata kuliah dari SIAKAD. Periksa konfigurasi API atau coba beberapa saat lagi.',
                ]);
            }

            if ($sections === []) {
                return view('edom.status', [
                    'edom' => $edom,
                    'statusTitle' => 'Tidak ada mata kuliah untuk EDOM ini',
                    'statusMessage' => 'Data KRS dari SIAKAD tidak memiliki mata kuliah yang cocok dengan setting EDOM ini.',
                ]);
            }
        }

        return view('edom.show', compact('edom', 'student', 'sections'));
    }

    public function submitFromHome(Request $request): RedirectResponse
    {
        return $this->submit($request, $request->input('edom_id'));
    }

    public function submit(Request $request, mixed $edom): RedirectResponse
    {
        $edom = $this->prepareSettingEdom($edom);

        if (! $edom->isActive()) {
            return redirect()->route('edom.home')->with('error', 'EDOM tidak sedang aktif.');
        }

        if (session('edom_student') && $request->has('sections')) {
            return $this->submitStudentSections($request, $edom);
        }

        return $this->submitLegacySingleForm($request, $edom);
    }

    private function submitStudentSections(Request $request, Model $edom): RedirectResponse
    {
        $student = session('edom_student');
        $questions = $edom->categories->flatMap(fn ($category) => $category->questions);
        $optionIds = $edom->questionOptions->pluck('id')->map(fn ($id) => (string) $id)->values()->all();
        $submittedSections = $request->input('sections', []);

        $rules = ['sections' => ['required', 'array', 'min:1']];

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
            foreach ($submittedSections as $sectionKey => $section) {
                $section = is_array($section) ? $section : [];
                $period = $this->firstOrCreatePeriod($student);

                $response = EdomResponse::updateOrCreate(
                    [
                        'edom_period_id' => $period->id,
                        'edom_setting_id' => $edom->id,
                        'siakad_idmahasiswa' => (string) $student['siakad_idmahasiswa'],
                        'siakad_idmatakuliah' => $this->nullableInteger($section['idmatakuliah'] ?? null),
                        'siakad_idtawarmatakuliahdetail' => $this->nullableInteger($section['idtawarmatakuliahdetail'] ?? null),
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

            if ($this->studentHasCompletedAllSections($student, $currentSections)) {
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

        return redirect()->route('edom.home')->with('success', 'Jawaban EDOM berhasil disimpan.');
    }

    private function submitLegacySingleForm(Request $request, Model $edom): RedirectResponse
    {
        $questions = $edom->categories->flatMap(fn ($category) => $category->questions);
        $optionIds = $edom->questionOptions->pluck('id')->map(fn ($id) => (string) $id)->values()->all();

        $rules = [];

        foreach ($questions as $question) {
            if ($this->isTextQuestion($question)) {
                $rules["essays.{$question->id}"] = ['nullable', 'string', 'max:5000'];
            } else {
                $rules["answers.{$question->id}"] = ['required', Rule::in($optionIds)];
            }
        }

        $request->validate($rules);

        DB::transaction(function () use ($request, $edom, $questions) {
            $response = EdomResponse::create([
                'edom_setting_id' => $edom->id,
                'submitted_at' => now(),
            ]);

            foreach ($questions as $question) {
                if ($this->isTextQuestion($question)) {
                    EdomResponseDetail::create([
                        'edom_response_id' => $response->id,
                        'edom_question_id' => $question->id,
                        'answer_text' => $request->input("essays.{$question->id}"),
                    ]);

                    continue;
                }

                EdomResponseDetail::create([
                    'edom_response_id' => $response->id,
                    'edom_question_id' => $question->id,
                    'edom_option_id' => (int) $request->input("answers.{$question->id}"),
                ]);
            }
        });

        return redirect()->route('edom.home')->with('success', 'Jawaban EDOM berhasil dikirim.');
    }

    private function prepareSettingEdom(mixed $edom): Model
    {
        $edom = $edom instanceof Model ? $edom : SettingEdom::query()->findOrFail($edom);

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

    private function programStudiIdsFromSections(array $sections): array
    {
        return collect($sections)
            ->pluck('id_unw_program_studi')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function scopeSettingEdomsForProgramStudi(Builder $query, array $programStudiIds): void
    {
        $query->where(function (Builder $query) use ($programStudiIds) {
            $query->whereDoesntHave('programStudis')
                ->orWhereHas('programStudis', function (Builder $query) use ($programStudiIds) {
                    $query->whereIn('program_studi.id_unw_program_studi', $programStudiIds);
                });
        });
    }

    private function sectionsForSettingEdom(Model $edom, array $sections): array
    {
        $settingProgramStudiIds = $edom->programStudis
            ->pluck('id_unw_program_studi')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->values()
            ->all();

        if ($settingProgramStudiIds === []) {
            return array_values($sections);
        }

        return collect($sections)
            ->filter(fn (array $section) => in_array((int) ($section['id_unw_program_studi'] ?? 0), $settingProgramStudiIds, true))
            ->values()
            ->all();
    }

    private function studentHasCompletedAllSections(array $student, array $sections): bool
    {
        if ($sections === []) {
            return false;
        }

        foreach ($sections as $section) {
            $query = EdomResponse::query()
                ->where('siakad_idmahasiswa', (string) $student['siakad_idmahasiswa']);

            $detailId = $this->nullableInteger($section['idtawarmatakuliahdetail'] ?? null);

            $detailId !== null
                ? $query->where('siakad_idtawarmatakuliahdetail', $detailId)
                : $query->whereNull('siakad_idtawarmatakuliahdetail');

            if (! $query->exists()) {
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
