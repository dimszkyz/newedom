<x-filament-panels::page>
    @php
        $edom = $this->getEdomSettings();
    @endphp

    <div class="edom-preview-page">
        <style>
            .edom-preview-table-wrap {
                overflow-x: auto;
                margin-top: 1rem;
                border: 1px solid #d1d5db;
                border-radius: 14px;
                background: #ffffff;
                box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            }

            .edom-preview-table {
                width: 100%;
                min-width: 920px;
                border-collapse: collapse;
                background: #ffffff;
                color: #111827;
            }

            .edom-preview-table th,
            .edom-preview-table td {
                border: 1px solid #d1d5db;
                padding: 12px 14px;
                color: #111827;
                vertical-align: top;
            }

            .edom-preview-table thead th {
                background: #f3f4f6;
                color: #111827;
                font-size: 13px;
                font-weight: 800;
                text-align: center;
                text-transform: uppercase;
                letter-spacing: 0.04em;
            }

            .edom-preview-number {
                width: 70px;
                text-align: center;
                font-weight: 700;
            }

            .edom-preview-statement {
                min-width: 420px;
                line-height: 1.6;
            }

            .edom-preview-category td {
                background: #eaf1fb;
                color: #022B63;
                font-weight: 900;
                text-transform: uppercase;
                letter-spacing: 0.04em;
            }

            .edom-preview-choice {
                width: 120px;
                text-align: center;
                color: #022B63;
                font-size: 22px;
                line-height: 1;
            }

            .edom-preview-empty td {
                background: #ffffff;
                color: #6b7280;
                text-align: center;
                font-style: italic;
            }

            .edom-preview-textarea {
                width: 100%;
                min-height: 84px;
                padding: 10px 12px;
                border: 1px solid #cbd5e1;
                border-radius: 10px;
                background: #f8fafc;
                color: #111827;
                resize: vertical;
            }
        </style>

        <div class="edom-preview-form">
            {{ $this->form }}
        </div>

        @if ($edom)
            <x-filament::section>
                <x-slot name="heading">Informasi Detail EdomSettings</x-slot>

                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Nama EdomSettings</div>
                        <div class="font-semibold">{{ $edom->edom_name }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Program Studi</div>
                        <div class="font-semibold">
                            {{ $edom->programStudis->map(fn ($programStudi) => $programStudi->display_name)->join(', ') ?: '-' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Status</div>
                        <div class="font-semibold">{{ strtoupper($edom->status ?? '-') }}</div>
                    </div>
                </div>
            </x-filament::section>

            <div class="edom-preview-table-wrap">
                <table class="edom-preview-table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Pernyataan Evaluasi</th>
                            @forelse ($edom->questionOptions as $option)
                                <th>{{ $option->label ?? $option->name ?? '-' }}</th>
                            @empty
                                <th>Jawaban</th>
                            @endforelse
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($edom->categories as $category)
                            <tr class="edom-preview-category">
                                <td colspan="{{ max($edom->questionOptions->count(), 1) + 2 }}">
                                    {{ strtoupper($category->category_name ?? $category->name ?? 'Kategori Tanpa Nama') }}
                                </td>
                            </tr>

                            @forelse ($category->questions as $question)
                                <tr>
                                    <td class="edom-preview-number">{{ $loop->iteration }}</td>
                                    <td class="edom-preview-statement">{{ $question->statement ?: '-' }}</td>

                                    @if (in_array(strtolower((string) $question->question_type), ['essay', 'esai']))
                                        <td colspan="{{ max($edom->questionOptions->count(), 1) }}">
                                            <textarea class="edom-preview-textarea" readonly placeholder="Jawaban essay mahasiswa akan diisi di sini..."></textarea>
                                        </td>
                                    @else
                                        @forelse ($edom->questionOptions as $option)
                                            <td class="edom-preview-choice">○</td>
                                        @empty
                                            <td class="edom-preview-choice">○</td>
                                        @endforelse
                                    @endif
                                </tr>
                            @empty
                                <tr class="edom-preview-empty">
                                    <td colspan="{{ max($edom->questionOptions->count(), 1) + 2 }}">
                                        Belum ada pernyataan pada kategori ini.
                                    </td>
                                </tr>
                            @endforelse
                        @empty
                            <tr class="edom-preview-empty">
                                <td colspan="{{ max($edom->questionOptions->count(), 1) + 2 }}">
                                    Belum ada kategori dan pernyataan pada EdomSettings ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-filament-panels::page>
