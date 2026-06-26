@extends('layouts.app')

@section('title', $edom->edom_name . ' - EDOM Universitas Ngudi Waluyo')

@section('content')
    @php
        $options = $edom->options;
        $sections = collect($sections ?? []);
        $student = $student ?? null;
        $hasQuestions = $edom->categories->sum(fn ($category) => $category->questions->count()) > 0;
        $hasOptionQuestions = $edom->categories
            ->flatMap(fn ($category) => $category->questions)
            ->contains(fn ($question) => ! in_array(strtolower((string) $question->question_type), ['essay', 'esai', 'uraian', 'text', 'textarea'], true));
        $canSubmit = $hasQuestions
            && (! $hasOptionQuestions || $options->isNotEmpty())
            && (! $student || $sections->isNotEmpty());
        $toRoman = function (int $number): string {
            $map = [
                'M' => 1000,
                'CM' => 900,
                'D' => 500,
                'CD' => 400,
                'C' => 100,
                'XC' => 90,
                'L' => 50,
                'XL' => 40,
                'X' => 10,
                'IX' => 9,
                'V' => 5,
                'IV' => 4,
                'I' => 1,
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
            $title = preg_replace('/^([IVXLCDM]+)\.\s+\1\.\s+/i', '$1. ', $title);

            if (! preg_match('/^[IVXLCDM]+\.\s+/i', $title)) {
                $title = $toRoman($index + 1) . '. ' . $title;
            }

            return $title;
        };

        $sectionKey = function (array $section, int $index): string {
            $detail = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) ($section['idtawarmatakuliahdetail'] ?? ''));

            return 's_' . $index . '_' . ($detail !== '' ? $detail : 'x');
        };

        $sectionLecturer = function (array $section): array {
            $lecturer = $section['dosen'] ?? [];

            return is_array($lecturer) ? $lecturer : [];
        };

        $sectionCourseTitle = function (array $section): string {
            $kode = trim((string) ($section['kode'] ?? ''));
            $nama = trim((string) ($section['nama'] ?? ''));

            return trim($kode . ' - ' . $nama, ' -') ?: 'Mata kuliah tanpa nama';
        };
    @endphp

    <main class="edom-public-main">
        <div class="edom-public-container">
            <form method="POST" action="{{ route('edom.home.submit') }}" class="edom-form-card">
                @csrf
                <input type="hidden" name="edom_id" value="{{ $edom->id }}">

                <section class="edom-intro-card" aria-labelledby="edom-intro-title">
                    <h1 id="edom-intro-title" class="edom-intro-title">EVALUASI DOSEN OLEH MAHASISWA</h1>
                    <p class="edom-intro-subtitle">Instrumen Penilaian Akademik (EDOM)</p>

                    @if ($student)
                        <div class="edom-alert edom-alert-success">
                            Mode mahasiswa aktif. Periode SIAKAD:
                            {{ $student['siakad_idtahunajaran'] ?? '-' }} / Semester {{ $student['siakad_idsemester'] ?? '-' }}.
                            Jumlah mata kuliah yang akan dievaluasi: {{ $sections->count() }}.
                        </div>
                    @endif

                    <div class="edom-guide-box">
                        <p class="edom-guide-title">
                            <span class="edom-guide-icon">i</span>
                            Petunjuk Pengisian
                        </p>
                        <p class="edom-guide-text">
                            Pilihlah satu jawaban yang paling mencerminkan kondisi nyata di kelas. Jika Anda masuk melalui SIAKAD, pertanyaan yang sama akan ditampilkan untuk setiap mata kuliah yang wajib dievaluasi.
                        </p>

                        @if ($options->isNotEmpty())
                            <div class="edom-scale-list" aria-label="Skala penilaian EDOM">
                                @foreach ($options as $option)
                                    @php
                                        $scaleValue = $option->score ?: $loop->iteration;
                                        $scaleLabel = ucwords(strtolower((string) $option->label));
                                        $scaleClass = (($loop->index % 6) + 1);
                                    @endphp
                                    <span class="edom-scale-badge scale-{{ $scaleClass }}">
                                        {{ $scaleValue }} = {{ $scaleLabel }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>

                @if (session('success'))
                    <div class="edom-alert edom-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="edom-alert edom-alert-error">
                        {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="edom-alert edom-alert-error">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if ($student && $sections->isNotEmpty())
                    @foreach ($sections as $sectionIndex => $section)
                        @php
                            $section = is_array($section) ? $section : [];
                            $key = $sectionKey($section, $sectionIndex);
                            $lecturer = $sectionLecturer($section);
                            $questionNumber = 1;
                        @endphp

                        <section class="edom-intro-card" aria-labelledby="section-title-{{ $key }}">
                            <h2 id="section-title-{{ $key }}" class="edom-intro-title">
                                {{ $sectionCourseTitle($section) }}
                            </h2>
                            <p class="edom-intro-subtitle">
                                Dosen: {{ $lecturer['nama'] ?? '-' }}
                                @if (! empty($lecturer['nidn']))
                                    (NIDN: {{ $lecturer['nidn'] }})
                                @endif
                            </p>
                            <p class="edom-guide-text">
                                Program Studi SIAKAD: {{ $section['id_unw_program_studi'] ?? '-' }} ·
                                ID Detail Penawaran: {{ $section['idtawarmatakuliahdetail'] ?? '-' }}
                            </p>
                        </section>

                        <input type="hidden" name="sections[{{ $key }}][idtawarmatakuliahdetail]" value="{{ $section['idtawarmatakuliahdetail'] ?? '' }}">
                        <input type="hidden" name="sections[{{ $key }}][idmatakuliah]" value="{{ $section['idmatakuliah'] ?? '' }}">
                        <input type="hidden" name="sections[{{ $key }}][kode]" value="{{ $section['kode'] ?? '' }}">
                        <input type="hidden" name="sections[{{ $key }}][nama]" value="{{ $section['nama'] ?? '' }}">
                        <input type="hidden" name="sections[{{ $key }}][id_unw_program_studi]" value="{{ $section['id_unw_program_studi'] ?? '' }}">
                        <input type="hidden" name="sections[{{ $key }}][dosen][nidn]" value="{{ $lecturer['nidn'] ?? '' }}">
                        <input type="hidden" name="sections[{{ $key }}][dosen][nama]" value="{{ $lecturer['nama'] ?? '' }}">

                        <div class="edom-table-area">
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
                                                <th>{{ $option->label }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @forelse ($edom->categories as $categoryIndex => $category)
                                            <tr class="edom-category-row">
                                                <td colspan="{{ $options->count() + 2 }}">
                                                    {{ strtoupper($formatCategoryTitle($category->category_name, $categoryIndex)) }}
                                                </td>
                                            </tr>

                                            @forelse ($category->questions as $question)
                                                <tr>
                                                    <td class="edom-question-number">{{ $questionNumber++ }}</td>
                                                    <td class="edom-question-text">{{ $question->statement }}</td>

                                                    @if (in_array(strtolower((string) $question->question_type), ['essay', 'esai', 'uraian', 'text', 'textarea'], true))
                                                        <td colspan="{{ max($options->count(), 1) }}">
                                                            <textarea
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
                                                        <td>
                                                            Opsi jawaban belum tersedia.
                                                        </td>
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
                    @endforeach
                @else
                    @php $questionNumber = 1; @endphp
                    <div class="edom-table-area">
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
                                            <th>{{ $option->label }}</th>
                                        @endforeach
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse ($edom->categories as $categoryIndex => $category)
                                        <tr class="edom-category-row">
                                            <td colspan="{{ $options->count() + 2 }}">
                                                {{ strtoupper($formatCategoryTitle($category->category_name, $categoryIndex)) }}
                                            </td>
                                        </tr>

                                        @forelse ($category->questions as $question)
                                            <tr>
                                                <td class="edom-question-number">{{ $questionNumber++ }}</td>
                                                <td class="edom-question-text">{{ $question->statement }}</td>

                                                @if (in_array(strtolower((string) $question->question_type), ['essay', 'esai', 'uraian', 'text', 'textarea'], true))
                                                    <td colspan="{{ max($options->count(), 1) }}">
                                                        <textarea
                                                            name="essays[{{ $question->id }}]"
                                                            class="edom-essay-input"
                                                            placeholder="Tulis jawaban Anda di sini..."
                                                        >{{ old("essays.{$question->id}") }}</textarea>
                                                    </td>
                                                @elseif ($options->isNotEmpty())
                                                    @foreach ($options as $option)
                                                        <td class="edom-choice-cell">
                                                            <label class="edom-choice-label" for="answer-{{ $question->id }}-{{ $option->id }}">
                                                                <input
                                                                    type="radio"
                                                                    id="answer-{{ $question->id }}-{{ $option->id }}"
                                                                    name="answers[{{ $question->id }}]"
                                                                    value="{{ $option->id }}"
                                                                    class="edom-choice-input"
                                                                    @checked(old("answers.{$question->id}") == $option->id)
                                                                    required
                                                                >
                                                            </label>
                                                        </td>
                                                    @endforeach
                                                @else
                                                    <td>
                                                        Opsi jawaban belum tersedia.
                                                    </td>
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
