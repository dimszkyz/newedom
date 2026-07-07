<x-filament-panels::page>
    @php
        $edom = $this->getEdomSettings();
    @endphp

    <div class="edom-preview-page">
        <style>
            .edom-preview-info-panel {
                overflow: hidden;
                border: 1px solid rgba(148, 163, 184, 0.22);
                border-radius: 20px;
                background:
                    radial-gradient(circle at top right, rgba(245, 158, 11, 0.13), transparent 34%),
                    linear-gradient(135deg, rgba(2, 43, 99, 0.18), rgba(15, 23, 42, 0.02));
            }

            .edom-preview-info-hero {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 20px;
                padding: 24px;
                border-bottom: 1px solid rgba(148, 163, 184, 0.18);
            }

            .edom-preview-info-title-wrap {
                display: flex;
                gap: 16px;
                align-items: flex-start;
                min-width: 0;
            }

            .edom-preview-info-icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                width: 46px;
                height: 46px;
                border-radius: 16px;
                background: linear-gradient(135deg, #f59e0b, #facc15);
                color: #111827;
                font-size: 20px;
                box-shadow: 0 14px 28px rgba(245, 158, 11, 0.22);
            }

            .edom-preview-info-kicker {
                margin: 0 0 4px;
                color: #f59e0b;
                font-size: 12px;
                font-weight: 800;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .edom-preview-info-title {
                margin: 0;
                color: #ffffff;
                font-size: 24px;
                font-weight: 800;
                line-height: 1.25;
            }

            .edom-preview-info-description {
                margin: 8px 0 0;
                max-width: 760px;
                color: #94a3b8;
                font-size: 14px;
                line-height: 1.6;
            }

            .edom-preview-status-pill {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                flex-shrink: 0;
                min-height: 36px;
                padding: 8px 13px;
                border-radius: 999px;
                background: rgba(22, 163, 74, 0.12);
                border: 1px solid rgba(34, 197, 94, 0.35);
                color: #86efac;
                font-size: 13px;
                font-weight: 800;
            }

            .edom-preview-status-pill::before {
                content: "";
                width: 8px;
                height: 8px;
                border-radius: 999px;
                background: #22c55e;
                box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.18);
            }

            .edom-preview-info-body {
                display: grid;
                grid-template-columns: minmax(260px, 0.8fr) minmax(0, 1.2fr);
                gap: 18px;
                padding: 20px 24px 24px;
            }

            .edom-preview-summary-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .edom-preview-summary-card,
            .edom-preview-program-card {
                border: 1px solid rgba(148, 163, 184, 0.18);
                border-radius: 16px;
                background: rgba(255, 255, 255, 0.04);
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03);
            }

            .edom-preview-summary-card {
                padding: 16px;
            }

            .edom-preview-summary-label {
                margin: 0 0 7px;
                color: #94a3b8;
                font-size: 11px;
                font-weight: 800;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .edom-preview-summary-value {
                color: #ffffff;
                font-size: 24px;
                font-weight: 900;
                line-height: 1;
            }

            .edom-preview-summary-note {
                margin-top: 7px;
                color: #94a3b8;
                font-size: 12px;
            }

            .edom-preview-program-card {
                padding: 16px;
            }

            .edom-preview-program-header {
                display: flex;
                justify-content: space-between;
                gap: 12px;
                align-items: center;
                margin-bottom: 12px;
            }

            .edom-preview-program-title {
                margin: 0;
                color: #ffffff;
                font-size: 15px;
                font-weight: 800;
            }

            .edom-preview-program-count {
                display: inline-flex;
                align-items: center;
                min-height: 28px;
                padding: 5px 10px;
                border-radius: 999px;
                background: rgba(2, 43, 99, 0.45);
                color: #bfdbfe;
                font-size: 12px;
                font-weight: 800;
                white-space: nowrap;
            }

            .edom-preview-program-list {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                max-height: 136px;
                overflow-y: auto;
                padding-right: 4px;
            }

            .edom-preview-program-chip {
                display: inline-flex;
                align-items: center;
                min-height: 30px;
                padding: 6px 10px;
                border: 1px solid rgba(148, 163, 184, 0.28);
                border-radius: 999px;
                background: rgba(15, 23, 42, 0.42);
                color: #e5e7eb;
                font-size: 12px;
                font-weight: 700;
                line-height: 1.2;
            }

            .edom-preview-empty-program {
                color: #94a3b8;
                font-size: 13px;
            }

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
                min-height: 96px;
                padding: 12px 14px;
                border: 1px solid #cbd5e1;
                border-radius: 10px;
                background: #f8fafc;
                color: #111827;
                font-family: inherit;
                font-size: 14px;
                line-height: 1.6;
                resize: vertical;
            }

            @media (max-width: 1024px) {
                .edom-preview-info-body {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 640px) {
                .edom-preview-info-hero {
                    flex-direction: column;
                    padding: 20px;
                }

                .edom-preview-info-body {
                    padding: 18px 20px 20px;
                }

                .edom-preview-summary-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>

        <div class="edom-preview-form">
            {{ $this->form }}
        </div>

        @if ($edom)
            @php
                $programStudiItems = $edom->programStudis
                    ->map(fn ($programStudi): string => $programStudi->display_name)
                    ->filter()
                    ->values();

                $questionCount = $edom->categories->sum(fn ($category) => $category->questions->count());
            @endphp

            <section class="edom-preview-info-panel" aria-label="Informasi EDOM Settings">
                <div class="edom-preview-info-hero">
                    <div class="edom-preview-info-title-wrap">
                        <div class="edom-preview-info-icon">
                            <i class="fa-solid fa-clipboard-check"></i>
                        </div>
                        <div>
                            <p class="edom-preview-info-kicker">Informasi EDOM Settings</p>
                            <h2 class="edom-preview-info-title">{{ $edom->edom_name ?: 'EDOM tanpa nama' }}</h2>
                            <p class="edom-preview-info-description">
                                Ringkasan struktur EDOM yang sedang dipilih untuk preview. Gunakan informasi ini untuk memastikan status, cakupan program studi, kategori, dan jumlah pernyataan sudah sesuai.
                            </p>
                        </div>
                    </div>

                    <span class="edom-preview-status-pill">
                        {{ $edom->status_label ?? strtoupper($edom->status ?? '-') }}
                    </span>
                </div>

                <div class="edom-preview-info-body">
                    <div class="edom-preview-summary-grid">
                        <div class="edom-preview-summary-card">
                            <p class="edom-preview-summary-label">Kategori</p>
                            <div class="edom-preview-summary-value">{{ $edom->categories->count() }}</div>
                            <div class="edom-preview-summary-note">Kelompok aspek evaluasi</div>
                        </div>

                        <div class="edom-preview-summary-card">
                            <p class="edom-preview-summary-label">Pernyataan</p>
                            <div class="edom-preview-summary-value">{{ $questionCount }}</div>
                            <div class="edom-preview-summary-note">Total butir pertanyaan</div>
                        </div>

                        <div class="edom-preview-summary-card">
                            <p class="edom-preview-summary-label">Program Studi</p>
                            <div class="edom-preview-summary-value">{{ $programStudiItems->count() }}</div>
                            <div class="edom-preview-summary-note">Cakupan penerapan EDOM</div>
                        </div>

                        <div class="edom-preview-summary-card">
                            <p class="edom-preview-summary-label">Status</p>
                            <div class="edom-preview-summary-value" style="font-size: 18px; line-height: 1.2;">{{ $edom->status_label ?? strtoupper($edom->status ?? '-') }}</div>
                            <div class="edom-preview-summary-note">Kondisi publikasi form</div>
                        </div>
                    </div>

                    <div class="edom-preview-program-card">
                        <div class="edom-preview-program-header">
                            <h3 class="edom-preview-program-title">Program Studi Terhubung</h3>
                            <span class="edom-preview-program-count">{{ $programStudiItems->count() }} Program Studi</span>
                        </div>

                        <div class="edom-preview-program-list">
                            @forelse ($programStudiItems as $programStudi)
                                <span class="edom-preview-program-chip">{{ $programStudi }}</span>
                            @empty
                                <span class="edom-preview-empty-program">Belum ada program studi yang terhubung.</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>

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
                                @php
                                    $questionType = strtolower(trim((string) $question->question_type));
                                    $isTextQuestion = in_array($questionType, ['text', 'textarea', 'essay', 'esai', 'isian', 'uraian'], true);
                                @endphp

                                <tr>
                                    <td class="edom-preview-number">{{ $loop->iteration }}</td>
                                    <td class="edom-preview-statement">{{ $question->statement ?: '-' }}</td>

                                    @if ($isTextQuestion)
                                        <td colspan="{{ max($edom->questionOptions->count(), 1) }}">
                                            <textarea class="edom-preview-textarea" readonly placeholder="Jawaban teks atau esai akan ditampilkan di sini..."></textarea>
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
