<?php

namespace App\Http\Controllers;

use App\Models\EdomAnswer;
use App\Models\EdomOption;
use App\Models\EdomQuestion;
use App\Models\EdomResponse;
use App\Models\SettingsEdom as Edom;
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

        $activeQuery = Edom::query()
            ->with(['prodis', 'mataKuliahs'])
            ->withCount(['categories', 'questions'])
            ->where('status', 'active')
            ->latest('created_date')
            ->latest('id');

        if ($student && $studentProgramStudiIds !== []) {
            $this->scopeEdomsForProgramStudi($activeQuery, $studentProgramStudiIds);
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

        $closedEdoms = Edom::query()
            ->with(['prodis', 'mataKuliahs'])
            ->where('status', 'closed')
            ->latest('created_date')
            ->latest('id')
            ->limit(6)
            ->get();

        $draftCount = Edom::query()
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
                'siakad_idmahasiswa' => (int) $payload['siakad_idmahasiswa'],
                'siakad_idtahunajaran' => (int) $payload['siakad_idtahunajaran'],
                'siakad_idsemester' => (int) $payload['siakad_idsemester'],
                'return_url' => $payload['return_url'] ?? null,
            ],
        ]);

        return redirect()->route('edom.home');
    }

    public function show(mixed $edom): View
    {
        $edom = $this->prepareEdom($edom);

        if (! $this->isActiveEdom($edom)) {
            return view('edom.status', [
                'edom' => $edom,
                'statusTitle' => $this->isDraftEdom($edom)
                    ? 'EDOM belum dibuka'
                    : 'EDOM sudah ditutup',
                'statusMessage' => $this->isDraftEdom($edom)
                    ? 'Form evaluasi ini masih berstatus draft, sehingga belum bisa diisi oleh mahasiswa.'
                    : 'Form evaluasi ini sudah ditutup dan tidak lagi menerima jawaban baru.',
            ]);
        }

        $student = session('edom_student');
        $sections = [];

        if ($student) {
            try {
                $sections = $this->sectionsForEdom($edom, $this->fetchStudentSections($student));
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
                    'statusMessage' => 'Data KRS dari SIAKAD tidak memiliki mata kuliah yang cocok dengan paket pertanyaan EDOM ini.',
                ]);
            }
        }

        return view('edom.show', [
            'edom' => $edom,
            'student' => $student,
            'sections' => $sections,
        ]);
    }

    public function submitFromHome(Request $request): RedirectResponse
    {
        return $this->submit($request, $request->input('edom_id'));
    }

    public function submit(Request $request, mixed $edom): RedirectResponse
    {
        $edom = $this->prepareEdom($edom);

        if (! $this->isActiveEdom($edom)) {
            return redirect()
                ->route('edom.home')
                ->with('error', 'EDOM tidak sedang aktif, sehingga jawaban tidak dapat dikirim.');
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
        $optionIds = $edom->options->pluck('id')->map(fn ($id) => (string) $id)->values()->all();
        $submittedSections = $request->input('sections', []);

        $rules = [
            'sections' => ['required', 'array', 'min:1'],
        ];

        foreach ($submittedSections as $sectionKey => $section) {
            foreach ($questions as $question) {
                if ($this->isEssayQuestion($question)) {
                    $rules["essays.{$sectionKey}.{$question->id}"] = ['nullable', 'string', 'max:5000'];
                } else {
                    $rules["answers.{$sectionKey}.{$question->id}"] = ['required', Rule::in($optionIds)];
                }
            }
        }

        $request->validate($rules, [
            'sections.required' => 'Data mata kuliah EDOM tidak ditemukan.',
            'answers.*.*.required' => 'Semua pernyataan evaluasi wajib dipilih untuk setiap mata kuliah.',
            'answers.*.*.in' => 'Opsi jawaban yang dipilih tidak valid.',
        ]);

        DB::transaction(function () use ($request, $edom, $questions, $submittedSections, $student) {
            foreach ($submittedSections as $sectionKey => $section) {
                $section = is_array($section) ? $section : [];
                $lecturer = $this->sectionLecturer($section);

                $response = EdomResponse::updateOrCreate(
                    [
                        'edom_id' => $edom->id,
                        'siakad_idmahasiswa' => $student['siakad_idmahasiswa'],
                        'siakad_idtahunajaran' => $student['siakad_idtahunajaran'],
                        'siakad_idsemester' => $student['siakad_idsemester'],
                        'siakad_idmatakuliah' => $this->nullableInteger($section['idmatakuliah'] ?? null),
                        'siakad_idtawarmatakuliahdetail' => $this->nullableInteger($section['idtawarmatakuliahdetail'] ?? null),
                    ],
                    [
                        'id_unw_program_studi' => $this->nullableInteger($section['id_unw_program_studi'] ?? null),
                        'edom_name_snapshot' => $this->getEdomName($edom),
                        'study_program_snapshot' => $this->studyProgramSnapshot($edom, $section),
                        'course_snapshot' => $this->courseSnapshot($section),
                        'lecturer_name_snapshot' => $lecturer['nama'] ?? null,
                        'lecturer_nidn_snapshot' => $lecturer['nidn'] ?? null,
                        'submitted_at' => now(),
                    ]
                );

                foreach ($questions as $question) {
                    $categoryName = $question->category?->category_name;

                    if ($this->isEssayQuestion($question)) {
                        EdomAnswer::updateOrCreate(
                            [
                                'edom_response_id' => $response->id,
                                'edom_question_id' => $question->id,
                            ],
                            [
                                'category_name_snapshot' => $categoryName,
                                'statement_snapshot' => $question->statement,
                                'edom_option_id' => null,
                                'option_label_snapshot' => null,
                                'option_score_snapshot' => null,
                                'answer_text' => $request->input("essays.{$sectionKey}.{$question->id}"),
                                'score' => null,
                            ]
                        );

                        continue;
                    }

                    $optionId = (int) $request->input("answers.{$sectionKey}.{$question->id}");
                    $option = $edom->options->firstWhere('id', $optionId);

                    EdomAnswer::updateOrCreate(
                        [
                            'edom_response_id' => $response->id,
                            'edom_question_id' => $question->id,
                        ],
                        [
                            'category_name_snapshot' => $categoryName,
                            'statement_snapshot' => $question->statement,
                            'edom_option_id' => $option?->id,
                            'option_label_snapshot' => $option?->label,
                            'option_score_snapshot' => $option?->score,
                            'answer_text' => null,
                            'score' => $option?->score,
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
                ->with('error', 'Jawaban tersimpan, tetapi status selesai belum dapat dikirim ke SIAKAD. Silakan coba buka EDOM kembali beberapa saat lagi.');
        }

        return redirect()
            ->route('edom.home')
            ->with('success', 'Jawaban EDOM berhasil disimpan. Masih ada mata kuliah lain yang perlu diisi sebelum dikirim selesai ke SIAKAD.');
    }

    private function submitLegacySingleForm(Request $request, Model $edom): RedirectResponse
    {
        $questions = $edom->categories->flatMap(fn ($category) => $category->questions);
        $optionIds = $edom->options->pluck('id')->map(fn ($id) => (string) $id)->values()->all();

        $rules = [
            'edom_id' => ['nullable', 'integer'],
            'respondent_name' => ['nullable', 'string', 'max:150'],
            'student_number' => ['nullable', 'string', 'max:50'],
        ];

        foreach ($questions as $question) {
            if ($this->isEssayQuestion($question)) {
                $rules["essays.{$question->id}"] = ['nullable', 'string', 'max:5000'];
            } else {
                $rules["answers.{$question->id}"] = ['required', Rule::in($optionIds)];
            }
        }

        $request->validate($rules, [
            'answers.*.required' => 'Semua pernyataan evaluasi wajib dipilih.',
            'answers.*.in' => 'Opsi jawaban yang dipilih tidak valid.',
        ]);

        DB::transaction(function () use ($request, $edom, $questions) {
            $response = EdomResponse::create([
                'edom_id' => $edom->id,
                'edom_name_snapshot' => $this->getEdomName($edom),
                'study_program_snapshot' => $edom->prodis->pluck('name')->filter()->join(', '),
                'course_snapshot' => $edom->mataKuliahs->pluck('name')->filter()->join(', '),
                'respondent_name' => $request->input('respondent_name'),
                'student_number' => $request->input('student_number'),
                'submitted_at' => now(),
            ]);

            foreach ($questions as $question) {
                $categoryName = $question->category?->category_name;

                if ($this->isEssayQuestion($question)) {
                    EdomAnswer::create([
                        'edom_response_id' => $response->id,
                        'edom_question_id' => $question->id,
                        'category_name_snapshot' => $categoryName,
                        'statement_snapshot' => $question->statement,
                        'answer_text' => $request->input("essays.{$question->id}"),
                    ]);

                    continue;
                }

                $optionId = (int) $request->input("answers.{$question->id}");
                $option = $edom->options->firstWhere('id', $optionId);

                EdomAnswer::create([
                    'edom_response_id' => $response->id,
                    'edom_question_id' => $question->id,
                    'category_name_snapshot' => $categoryName,
                    'statement_snapshot' => $question->statement,
                    'edom_option_id' => $option?->id,
                    'option_label_snapshot' => $option?->label,
                    'option_score_snapshot' => $option?->score,
                    'score' => $option?->score,
                ]);
            }
        });

        return redirect()
            ->route('edom.home')
            ->with('success', 'Terima kasih, jawaban EDOM Anda berhasil dikirim.');
    }

    private function prepareEdom(mixed $edom): Model
    {
        $edom = $this->resolveEdom($edom);

        $edom->load([
            'prodis',
            'mataKuliahs',
            'categories' => fn ($query) => $query->orderBy('id'),
            'categories.questions' => fn ($query) => $query->orderBy('id'),
            'options' => fn ($query) => $query->orderBy('sort_order')->orderBy('score')->orderBy('id'),
        ]);

        if ($edom->options->isEmpty()) {
            $edom->setRelation(
                'options',
                EdomOption::query()
                    ->orderBy('sort_order')
                    ->orderBy('score')
                    ->orderBy('id')
                    ->get()
            );
        }

        return $edom;
    }

    private function resolveEdom(mixed $edom): Model
    {
        if ($edom instanceof Model) {
            return $edom;
        }

        return Edom::query()->findOrFail($edom);
    }

    private function fetchStudentSections(array $student): array
    {
        $sections = app(UnwApiSiakad::class)->krs(
            $student['siakad_idmahasiswa'],
            $student['siakad_idtahunajaran'],
            $student['siakad_idsemester']
        );

        return collect($sections)
            ->filter(fn ($section) => is_array($section))
            ->values()
            ->all();
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

    private function scopeEdomsForProgramStudi(Builder $query, array $programStudiIds): void
    {
        $query->where(function (Builder $query) use ($programStudiIds) {
            $query
                ->whereDoesntHave('prodis')
                ->orWhereHas('prodis', function (Builder $query) use ($programStudiIds) {
                    $query->whereIn('program_studi.id_unw_program_studi', $programStudiIds);
                });
        });
    }

    private function sectionsForEdom(Model $edom, array $sections): array
    {
        $edomProgramStudiIds = $edom->prodis
            ->pluck('id_unw_program_studi')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->values()
            ->all();

        if ($edomProgramStudiIds === []) {
            return array_values($sections);
        }

        return collect($sections)
            ->filter(fn (array $section) => in_array((int) ($section['id_unw_program_studi'] ?? 0), $edomProgramStudiIds, true))
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
                ->where('siakad_idmahasiswa', $student['siakad_idmahasiswa'])
                ->where('siakad_idtahunajaran', $student['siakad_idtahunajaran'])
                ->where('siakad_idsemester', $student['siakad_idsemester']);

            $detailId = $this->nullableInteger($section['idtawarmatakuliahdetail'] ?? null);
            $courseId = $this->nullableInteger($section['idmatakuliah'] ?? null);

            if ($detailId !== null) {
                $query->where('siakad_idtawarmatakuliahdetail', $detailId);
            } else {
                $query->whereNull('siakad_idtawarmatakuliahdetail');
            }

            if ($courseId !== null) {
                $query->where('siakad_idmatakuliah', $courseId);
            }

            if (! $query->exists()) {
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

    private function sectionLecturer(array $section): array
    {
        $lecturer = $section['dosen'] ?? [];

        return is_array($lecturer) ? $lecturer : [];
    }

    private function courseSnapshot(array $section): ?string
    {
        $kode = trim((string) ($section['kode'] ?? ''));
        $nama = trim((string) ($section['nama'] ?? ''));

        return trim($kode.' - '.$nama, ' -') ?: null;
    }

    private function studyProgramSnapshot(Model $edom, array $section): ?string
    {
        $programStudiId = (int) ($section['id_unw_program_studi'] ?? 0);

        if ($programStudiId > 0) {
            $prodiName = $edom->prodis
                ->firstWhere('id_unw_program_studi', $programStudiId)
                ?->name;

            if ($prodiName) {
                return $prodiName;
            }

            return (string) $programStudiId;
        }

        return $edom->prodis->pluck('name')->filter()->join(', ') ?: null;
    }

    private function nullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function isActiveEdom(Model $edom): bool
    {
        if (method_exists($edom, 'isActive')) {
            return $edom->isActive();
        }

        return $edom->status === 'active';
    }

    private function isDraftEdom(Model $edom): bool
    {
        if (method_exists($edom, 'isDraft')) {
            return $edom->isDraft();
        }

        return $edom->status === 'draft';
    }

    private function getEdomName(Model $edom): ?string
    {
        return $edom->edom_name ?? $edom->name ?? null;
    }

    private function isEssayQuestion(EdomQuestion $question): bool
    {
        return in_array(strtolower((string) $question->question_type), ['essay', 'esai', 'uraian', 'text', 'textarea'], true);
    }
}
