@extends('layouts.app')

@section('title', $edom->name . ' - EDOM Universitas Ngudi Waluyo')

@section('content')
    @php
        $options = $edom->questionOptions;
        $sections = collect($sections ?? []);
        $student = $student ?? null;
        $questions = $edom->categories->flatMap(fn ($category) => $category->questions);
        $hasQuestions = $questions->count() > 0;
        $hasOptionQuestions = $questions->contains(fn ($question) => ! in_array(strtolower((string) $question->question_type), ["text", "essay", "esai", "uraian", "textarea"], true));
        $canSubmit = $hasQuestions
            && (! $hasOptionQuestions || $options->isNotEmpty())
            && ($student && $sections->isNotEmpty());

        $toRoman = function (int $number): string {
            $map = [
                'M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400,
                'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40,
                'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1,
            ];

            $result = '';

            foreach ($map as $roman => $value) {
                while ($number >= $value) {
                    $result .= $roman;
                    $number -= $value;
                }
            }

            return $result;
        };

        $formatCategoryTitle = function (?string $title, int $index) use ($toRoman): string {
            $title = trim((string) $title);
            $title = $title !== '' ? $title : 'Kategori ' . ($index + 1);

            if (! preg_match('/^[IVXLCDM]+\.\s+/i', $title)) {
                $title = $toRoman($index + 1) . '. ' . $title;
            }

            return $title;
        };

        $sectionKey = function (array $section, int $index): string {
            $detail = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) ($section['idtawarmatakuliahdetail'] ?? ''));
            $course = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) ($section['idmatakuliah'] ?? ''));
            $identity = $detail !== '' ? 'd_' . $detail : ($course !== '' ? 'm_' . $course : 'x');

            return 's_' . $index . '_' . $identity;
        };

        $sectionLecturer = function (array $section): array {
            $lecturer = $section['dosen'] ?? [];

            return is_array($lecturer) ? $lecturer : [];
        };

        $sectionTeamLecturers = function (array $section): array {
            $team = $section['dosen_team'] ?? [];

            if (is_string($team)) {
                $team = [$team];
            }

            if (! is_array($team)) {
                return [];
            }

            return collect($team)
                ->map(function (mixed $lecturer): string {
                    if (is_array($lecturer)) {
                        return trim((string) ($lecturer['nama'] ?? $lecturer['nidn'] ?? ''));
                    }

                    return trim((string) $lecturer);
                })
                ->filter()
                ->values()
                ->all();
        };

        $sectionTitle = function (array $section): string {
            $kode = trim((string) ($section['kode'] ?? ''));
            $nama = trim((string) ($section['nama'] ?? ''));

            return trim($kode . ' - ' . $nama, ' -') ?: 'Mata kuliah tanpa nama';
        };
    @endphp

    <main class="page">
        <div class="container">
            <form method="POST" action="{{ route('edom.home.submit') }}" class="edom-form-card">
                @csrf
                <input type="hidden" name="edom_id" value="{{ $edom->id }}">

                <section class="hero" aria-labelledby="edom-intro-title">
                    <p class="eyebrow">Evaluasi Dosen Oleh Mahasiswa</p>
                    <h1 id="edom-intro-title">{{ $edom->name }}</h1>

                    @if ($student)
                        <div class="alert success">
                            Mode mahasiswa aktif. Periode SIAKAD:
                            {{ $student['siakad_idtahunajaran'] ?? '-' }} / Semester {{ $student['siakad_idsemester'] ?? '-' }}.
                            Jumlah mata kuliah yang akan dievaluasi: {{ $sections->count() }}.
                        </div>
                    @else
                        <div class="alert error">
                            Pengisian EDOM harus dibuka melalui SIAKAD.
                        </div>
                    @endif

                    <div class="edom-guide-box" style="margin-top: 18px;">
                        <p class="edom-guide-title">
                            <span class="edom-guide-icon">i</span>
                            Petunjuk Pengisian
                        </p>
                        <p class="edom-guide-text">
                            Pilihlah satu jawaban yang paling mencerminkan kondisi nyata di kelas dengan memberikan tanda centang atau klik pada salah satu skala berikut.
                        </p>

                        @if ($options->isNotEmpty())
                            <div class="edom-scale-list" aria-label="Skala penilaian EDOM">
                                @foreach ($options as $option)
                                    <span class="edom-scale-badge scale-{{ (($loop->index % 6) + 1) }}">
                                        {{ $option->score }} = {{ ucwords(strtolower((string) $option->name)) }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>

                @if (session('success'))
                    <div class="alert success">{{ session('success') }}</div>
                @endif

                @if (session('error'))
                    <div class="alert error">{{ session('error') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert error">{{ $errors->first() }}</div>
                @endif

                @if ($student && $sections->isNotEmpty())
                    <section class="section">
                        <h2 class="section-title">Mata Kuliah yang Dievaluasi</h2>

                        @foreach ($sections as $sectionIndex => $section)
                            @php
                                $section = is_array($section) ? $section : [];
                                $key = $sectionKey($section, $sectionIndex);
                                $lecturer = $sectionLecturer($section);
                                $teamLecturers = $sectionTeamLecturers($section);
                                $questionNumber = 1;
                            @endphp

                            <div class="course-group">
                                <div class="course-list" aria-labelledby="section-title-{{ $key }}">
                                    <div class="course-list-item">
                                        <div class="course-list-number">{{ $loop->iteration }}</div>

                                        <div class="course-list-content">
                                            <h2 id="section-title-{{ $key }}">{{ $sectionTitle($section) }}</h2>
                                            <div class="course-list-meta">
                                                <span>Dosen: {{ $lecturer['nama'] ?? '-' }}</span>
                                                @if (! empty($lecturer['nidn']))
                                                    <span>NIDN: {{ $lecturer['nidn'] }}</span>
                                                @endif
                                                @if ($teamLecturers !== [])
                                                    <span class="course-list-team">
                                                        Tim dosen: {{ implode(', ', $teamLecturers) }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <input type="hidden" name="sections[{{ $key }}][idtawarmatakuliahdetail]" value="{{ $section['idtawarmatakuliahdetail'] ?? '' }}">
                                <input type="hidden" name="sections[{{ $key }}][idmatakuliah]" value="{{ $section['idmatakuliah'] ?? '' }}">
                                <input type="hidden" name="sections[{{ $key }}][kode]" value="{{ $section['kode'] ?? '' }}">
                                <input type="hidden" name="sections[{{ $key }}][nama]" value="{{ $section['nama'] ?? '' }}">
                                <input type="hidden" name="sections[{{ $key }}][dosen][nidn]" value="{{ $lecturer['nidn'] ?? '' }}">
                                <input type="hidden" name="sections[{{ $key }}][dosen][nama]" value="{{ $lecturer['nama'] ?? '' }}">

                                <div class="section edom-table-area">
                                    <div class="edom-table-wrap">
                                        <table class="edom-input-table">
                                            <colgroup>
                                                <col class="edom-col-number">
                                                <col class="edom-col-statement">
                                                @foreach ($options as $option)
                                                    <col class="edom-col-option">
                                                @endforeach
                                            </colgroup>

                                            <thead>
                                                <tr>
                                                    <th>No.</th>
                                                    <th>Pernyataan Evaluasi</th>
                                                    @foreach ($options as $option)
                                                        <th>{{ $option->name }}</th>
                                                    @endforeach
                                                </tr>
                                            </thead>

                                            <tbody>
                                                @forelse ($edom->categories as $categoryIndex => $category)
                                                    <tr class="edom-category-row">
                                                        <td colspan="{{ $options->count() + 2 }}">
                                                            {{ strtoupper($formatCategoryTitle($category->name, $categoryIndex)) }}
                                                        </td>
                                                    </tr>

                                                    @forelse ($category->questions as $question)
                                                        <tr>
                                                            <td class="edom-question-number">{{ $questionNumber++ }}</td>
                                                            <td class="edom-question-text">{{ $question->statement }}</td>

                                                            @if (in_array(strtolower((string) $question->question_type), ["text", "essay", "esai", "uraian", "textarea"], true))
                                                                <td colspan="{{ max($options->count(), 1) }}">
                                                                    <textarea
                                                                        {{-- name="texts[{{ $key }}][{{ $question->id }}]" --}}
                                                                        name="essays[{{ $key }}][{{ $question->id }}]"
                                                                        class="edom-essay-input"
                                                                        placeholder="Tulis jawaban Anda di sini..."
                                                                    >{{ old('essays.' . $key . '.' . $question->id) }}</textarea>
                                                                </td>
                                                            @elseif ($options->isNotEmpty())
                                                                @foreach ($options as $option)
                                                                    <td class="edom-choice-cell">
                                                                        <label class="edom-choice-label" for="answer-{{ $key }}-{{ $question->id }}-{{ $option->id }}">
                                                                            <input
                                                                                type="radio"
                                                                                id="answer-{{ $key }}-{{ $question->id }}-{{ $option->id }}"
                                                                                name="answers[{{ $key }}][{{ $question->id }}]"
                                                                                value="{{ $option->id }}"
                                                                                class="edom-choice-input"
                                                                                @checked(old('answers.' . $key . '.' . $question->id) == $option->id)
                                                                                required
                                                                            >
                                                                        </label>
                                                                    </td>
                                                                @endforeach
                                                            @else
                                                                <td>Opsi jawaban belum tersedia.</td>
                                                            @endif
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="{{ $options->count() + 2 }}" class="edom-empty-state">
                                                                Belum ada pernyataan pada kategori ini.
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                @empty
                                                    <tr>
                                                        <td colspan="{{ $options->count() + 2 }}" class="edom-empty-state">
                                                            Belum ada kategori dan pernyataan EDOM yang bisa ditampilkan.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </section>
                @endif

                <div class="edom-action-bar">
                    <button type="submit" class="edom-submit-button" @disabled(! $canSubmit)>
                        Kirim Jawaban
                    </button>
                </div>
            </form>
        </div>
    </main>
@endsection
