<x-filament-panels::page>
    @php
        $edom = $this->getEdom();
    @endphp

    <style>
        .edom-preview-page {
            font-family: Arial, Helvetica, sans-serif;
        }

        .edom-preview-form {
            margin-bottom: 24px;
        }

        .edom-info-card {
            background-color: rgb(255, 255, 255);
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 24px;
            border: 1px solid rgb(229, 231, 235);
        }

        .dark .edom-info-card {
            background-color: rgb(24, 24, 27);
            border-color: rgb(63, 63, 70);
        }

        .edom-info-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1.5rem;
        }

        @media (min-width: 768px) {
            .edom-info-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1024px) {
            .edom-info-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .edom-info-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: rgb(107, 114, 128);
            margin-bottom: 0.25rem;
        }

        .dark .edom-info-label {
            color: rgb(161, 161, 170);
        }

        .edom-info-value {
            font-size: 1rem;
            font-weight: 600;
            color: rgb(17, 24, 39);
        }

        .dark .edom-info-value {
            color: rgb(250, 250, 250);
        }

        .edom-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 9999px;
            padding: 0.125rem 0.625rem;
            font-size: 0.75rem;
            font-weight: 600;
            background-color: rgb(243, 244, 246);
            color: rgb(55, 65, 81);
        }

        .dark .edom-badge {
            background-color: rgb(39, 39, 42);
            color: rgb(228, 228, 231);
        }

        .edom-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .edom-preview-table {
            width: 100%;
            min-width: 920px;
            border-collapse: collapse;
            table-layout: fixed;
            background: #ffffff;
            border: 1px solid #5a5a5a;
        }

        .edom-preview-table th,
        .edom-preview-table td {
            border: 1px solid #5a5a5a;
            color: #222;
            padding: 10px 12px;
            vertical-align: middle;
        }

        .edom-preview-table thead th {
            font-size: 15px;
            font-weight: 700;
            text-align: center;
            line-height: 1.15;
            background: #f9f9f9;
        }

        .edom-col-no {
            width: 6.5%;
            text-align: center;
        }

        .edom-col-statement {
            width: 61%;
            font-size: 15px;
            line-height: 1.45;
            text-align: left;
        }

        .edom-col-opt {
            width: 8.125%;
            text-align: center;
        }

        .edom-section-row td {
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 12px 14px;
            background: #ffffff;
        }

        .edom-question-row td {
            height: 64px;
        }

        .edom-no {
            font-size: 15px;
            text-align: center;
        }

        .edom-radio {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 21px;
            height: 21px;
            border: 1.8px solid #a7a7a7;
            border-radius: 9999px;
            background: #ffffff;
            box-sizing: border-box;
        }

        .edom-radio::after {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 9999px;
            background: transparent;
        }

        .edom-essay-box {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: #fdfdfd;
            resize: vertical;
            min-height: 60px;
            font-family: inherit;
            color: #555;
            box-sizing: border-box;
        }

        .edom-empty {
            padding: 22px 0;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
    </style>

    <div class="edom-preview-page">
        <div class="edom-preview-form">
            {{ $this->form }}
        </div>

        @if ($edom)
            <div class="edom-info-card">
                <h3 class="edom-info-value" style="margin-bottom: 1rem; font-size: 1.125rem;">Informasi Detail EDOM</h3>
                <div class="edom-info-grid">
                    <div>
                        <div class="edom-info-label">Nama EDOM</div>
                        <div class="edom-info-value">{{ $edom->edom_name }}</div>
                    </div>
                    <div>
                        <div class="edom-info-label">Program Studi (Prodi)</div>
                        <div class="edom-info-value">
                            {{ $edom->prodis->pluck('name')->join(', ') ?: '-' }}
                        </div>
                    </div>
                    <div>
                        <div class="edom-info-label">Mata Kuliah</div>
                        <div class="edom-info-value">
                            {{ $edom->mataKuliahs->pluck('name')->join(', ') ?: '-' }}
                        </div>
                    </div>
                    <div>
                        <div class="edom-info-label">Kategori</div>
                        <div class="edom-info-value">{{ $edom->categories->count() }} Kategori</div>
                    </div>
                    <div>
                        <div class="edom-info-label">Pertanyaan</div>
                        <div class="edom-info-value">
                            {{ $edom->categories->sum(fn($category) => $category->questions->count()) }} Pertanyaan
                        </div>
                    </div>
                    <div>
                        <div class="edom-info-label">Status</div>
                        <div class="edom-info-value">
                            <span class="edom-badge">{{ strtoupper($edom->status ?? '-') }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="edom-info-label">Dibuat Pada</div>
                        <div class="edom-info-value">{{ $edom->created_at ? $edom->created_at->format('d M Y H:i') : '-' }}</div>
                    </div>
                </div>
            </div>

            <div class="edom-table-wrap">
                <table class="edom-preview-table">
                    <colgroup>
                        <col class="edom-col-no">
                        <col class="edom-col-statement">
                        @foreach ($edom->options as $option)
                            <col class="edom-col-opt">
                        @endforeach
                    </colgroup>

                    <thead>
                        <tr>
                            <th style="width:50px">No.</th>
                            <th>Pernyataan Evaluasi</th>

                            @foreach ($edom->options as $option)
                                <th style="width:75px">
                                    {{ $option->label }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($edom->categories as $category)
                            <tr class="edom-section-row">
                                <td colspan="{{ $edom->options->count() + 2 }}">
                                    {{ strtoupper($category->category_name) }}
                                </td>
                            </tr>

                            @foreach ($category->questions as $question)
                                <tr>
                                    <td class="text-center">
                                        {{ $loop->iteration }}
                                    </td>

                                    <td>
                                        {{ $question->statement }}
                                    </td>

                                    @if(in_array(strtolower($question->question_type), ['essay', 'esai']))
                                        <td colspan="{{ $edom->options->count() }}" style="padding: 10px;">
                                            <textarea class="edom-essay-box" placeholder="Jawaban essay mahasiswa akan diisi di sini..." readonly></textarea>
                                        </td>
                                    @else
                                        @foreach ($edom->options as $option)
                                            <td class="text-center">
                                                <span class="edom-radio"></span>
                                            </td>
                                        @endforeach
                                    @endif

                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-filament-panels::page>
