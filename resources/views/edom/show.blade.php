@extends('layouts.app')

@section('title', $edom->edom_name . ' - EDOM Universitas Ngudi Waluyo')

@section('content')
    @php
        $options = $edom->options;
        $hasQuestions = $edom->categories->sum(fn ($category) => $category->questions->count()) > 0;
        $canSubmit = $hasQuestions && $options->isNotEmpty();
        $questionNumber = 1;
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
    @endphp

    <main class="edom-public-main">
        <div class="edom-public-container">
            <form method="POST" action="{{ route('edom.home.submit') }}" class="edom-form-card">
                @csrf
                <input type="hidden" name="edom_id" value="{{ $edom->id }}">

                <section class="edom-intro-card" aria-labelledby="edom-intro-title">
                    <h1 id="edom-intro-title" class="edom-intro-title">EVALUASI DOSEN OLEH MAHASISWA</h1>
                    <p class="edom-intro-subtitle">Instrumen Penilaian Akademik (EDOM)</p>

                    <div class="edom-guide-box">
                        <p class="edom-guide-title">
                            <span class="edom-guide-icon">i</span>
                            Petunjuk Pengisian
                        </p>
                        <p class="edom-guide-text">
                            Pilihlah satu jawaban yang paling mencerminkan kondisi nyata di kelas dengan memberikan tanda centang atau klik pada salah satu skala di dalam tabel berikut:
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

                @if ($errors->any())
                    <div class="edom-alert edom-alert-error">
                        {{ $errors->first() }}
                    </div>
                @endif

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

                <div class="edom-action-bar">
                    <button type="submit" class="edom-submit-button" @disabled(! $canSubmit)>
                        Kirim Jawaban
                    </button>
                </div>
            </form>
        </div>
    </main>
@endsection
