@extends('layouts.app')

@section('title', 'EDOM Universitas Ngudi Waluyo')

@section('styles')
    <style>
        .public-home {
            position: relative;
            overflow: hidden;
            padding-top: 52px;
            background:
                radial-gradient(circle at top left, rgba(2, 43, 99, 0.09), transparent 32%),
                radial-gradient(circle at top right, rgba(255, 193, 7, 0.14), transparent 26%),
                #f4f7fb;
        }

        .public-home::before {
            content: "";
            position: absolute;
            inset: 0 0 auto;
            height: 260px;
            background: linear-gradient(135deg, rgba(2, 43, 99, 0.08), rgba(255, 255, 255, 0));
            pointer-events: none;
        }

        .public-home .container {
            position: relative;
            z-index: 1;
        }

        .public-hero-landing {
            position: relative;
            overflow: hidden;
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(280px, 0.9fr);
            gap: 32px;
            align-items: stretch;
            min-height: 310px;
            padding: 0;
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 28px;
            background: #ffffff;
            box-shadow: 0 24px 70px rgba(2, 43, 99, 0.1);
        }

        .public-hero-copy,
        .public-hero-panel {
            position: relative;
            z-index: 1;
        }

        .public-hero-copy {
            padding: 40px 44px;
        }

        .public-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
            padding: 8px 12px;
            border-radius: 999px;
            background: #eef4ff;
            color: #022B63;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .public-kicker::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #16a34a;
            box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.14);
        }

        .public-hero-title {
            margin: 0;
            max-width: 680px;
            color: #022B63;
            font-size: clamp(30px, 4vw, 48px);
            line-height: 1.08;
            font-weight: 900;
            letter-spacing: -1.1px;
        }

        .public-hero-lead {
            max-width: 680px;
            margin: 18px 0 0;
            color: #475569;
            font-size: 16px;
            line-height: 1.75;
        }

        .public-hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 28px;
        }

        .public-info-pill {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            min-height: 42px;
            padding: 10px 15px;
            border-radius: 999px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #334155;
            font-size: 14px;
            font-weight: 800;
        }

        .public-info-pill i {
            color: #FFC107;
        }

        .public-hero-panel {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 16px;
            margin: 24px 24px 24px 0;
            padding: 26px;
            border-radius: 24px;
            background: linear-gradient(160deg, #022B63 0%, #013a86 100%);
            color: #ffffff;
            box-shadow: 0 20px 45px rgba(2, 43, 99, 0.24);
        }

        .public-hero-panel h3 {
            margin: 0;
            font-size: 18px;
            line-height: 1.35;
            color: #ffffff;
        }

        .public-step-list {
            display: grid;
            gap: 12px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .public-step-list li {
            display: grid;
            grid-template-columns: 34px minmax(0, 1fr);
            gap: 12px;
            align-items: start;
            color: #dbeafe;
            font-size: 14px;
            line-height: 1.55;
        }

        .public-step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.14);
            color: #FFC107;
            font-weight: 900;
        }

        .public-step-list strong {
            display: block;
            margin-bottom: 2px;
            color: #ffffff;
            font-size: 14px;
        }

        .public-section-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 18px;
        }

        .public-section-heading .section-title {
            margin-bottom: 4px;
            font-size: 24px;
            font-weight: 900;
        }

        .public-section-subtitle {
            margin: 0;
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
        }

        .public-edom-grid {
            grid-template-columns: repeat(auto-fit, minmax(310px, 1fr));
            gap: 18px;
        }

        .public-edom-card {
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-height: 280px;
            padding: 24px;
            border-radius: 22px;
            box-shadow: 0 16px 42px rgba(2, 43, 99, 0.08);
        }

        .public-edom-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #022B63, #FFC107);
        }

        .public-edom-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 18px;
        }

        .public-edom-card h2 {
            margin: 0;
            color: #022B63;
            font-size: 22px;
            line-height: 1.25;
            font-weight: 900;
        }

        .public-edom-programs {
            flex: 1;
            margin: 0;
            color: #475569;
            font-size: 14px;
            line-height: 1.75;
        }

        .public-edom-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 18px 0 20px;
        }

        .public-edom-meta span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 12px;
            border-radius: 999px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #334155;
            font-size: 13px;
            font-weight: 800;
        }

        .public-edom-meta i {
            color: #022B63;
        }

        .public-edom-action {
            align-self: flex-start;
            min-height: 40px;
            border-radius: 999px;
        }

        @media (max-width: 900px) {
            .public-hero-landing {
                grid-template-columns: 1fr;
            }

            .public-hero-panel {
                margin: 0 24px 24px;
            }
        }

        @media (max-width: 640px) {
            .public-home {
                padding-top: 28px;
            }

            .public-hero-copy {
                padding: 30px 22px;
            }

            .public-hero-panel {
                margin: 0 16px 18px;
                padding: 22px;
            }

            .public-section-heading {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
@endsection

@section('content')
    @php
        $student = $student ?? null;
        $studentSemester = $studentSemester ?? null;
        $studentSemesterFetchError = $studentSemesterFetchError ?? null;
        $studentProfile = $studentProfile ?? null;
        $studentProfileFetchError = $studentProfileFetchError ?? null;
        $studentSections = collect($studentSections ?? []);
        $studentEdomSections = collect($studentEdomSections ?? []);
        $studentFetchError = $studentFetchError ?? null;

        $formatLecturer = function (mixed $lecturer): ?string {
            if (is_string($lecturer)) {
                $name = trim($lecturer);

                return $name !== '' ? $name : null;
            }

            if (! is_array($lecturer)) {
                return null;
            }

            $name = trim((string) ($lecturer['nama'] ?? ''));
            $nidn = trim((string) ($lecturer['nidn'] ?? ''));

            if ($name === '') {
                return $nidn !== '' ? $nidn : null;
            }

            return $nidn !== '' ? $name . ' (' . $nidn . ')' : $name;
        };

        $sectionTeamLecturers = function (array $section) use ($formatLecturer): array {
            $team = $section['dosen_team'] ?? [];

            if (is_string($team)) {
                $team = [$team];
            } elseif (
                is_array($team)
                && (array_key_exists('nama', $team) || array_key_exists('nidn', $team))
            ) {
                $team = [$team];
            }

            if (! is_array($team)) {
                return [];
            }

            return collect($team)
                ->map($formatLecturer)
                ->filter()
                ->unique()
                ->values()
                ->all();
        };

        $sectionTitle = function (array $section): string {
            $code = trim((string) ($section['kode'] ?? ''));
            $name = trim((string) ($section['nama'] ?? ''));

            return trim($code . ' - ' . $name, ' -') ?: 'Mata kuliah tanpa nama';
        };

        $programStudiLabels = function ($programStudis): string {
            $labels = collect($programStudis)
                ->map(fn ($programStudi): string => $programStudi->display_name)
                ->filter()
                ->values();

            return $labels->join(', ') ?: 'Semua Program Studi';
        };
    @endphp

    <main class="page public-home">
        <div class="container">
            <section class="hero {{ $student ? '' : 'public-hero-landing' }}">
                @unless ($student)
                    <div class="public-hero-copy">
                        <span class="public-kicker">Sistem EDOM Pascasarjana</span>
                        <h1 class="public-hero-title">Evaluasi pembelajaran yang terarah dan terintegrasi SIAKAD.</h1>
                        <p class="public-hero-lead">
                            Halaman ini menampilkan informasi EDOM yang sedang aktif. Pengisian dilakukan melalui akses resmi dari SIAKAD agar data mata kuliah, dosen, dan periode akademik tetap valid.
                        </p>

                        <div class="public-hero-actions" aria-label="Informasi EDOM">
                            <span class="public-info-pill">
                                <i class="fa-solid fa-shield-halved"></i>
                                Terhubung SIAKAD
                            </span>
                            <span class="public-info-pill">
                                <i class="fa-solid fa-circle-check"></i>
                                Data KRS tervalidasi
                            </span>
                        </div>
                    </div>

                    <aside class="public-hero-panel" aria-label="Panduan singkat EDOM">
                        <h3>Alur pengisian EDOM mahasiswa</h3>
                        <ol class="public-step-list">
                            <li>
                                <span class="public-step-number">1</span>
                                <span>
                                    <strong>Buka menu EDOM di SIAKAD</strong>
                                    Sistem akan membaca periode akademik yang sedang berjalan.
                                </span>
                            </li>
                            <li>
                                <span class="public-step-number">2</span>
                                <span>
                                    <strong>Pilih mata kuliah</strong>
                                    Daftar mata kuliah ditampilkan sesuai data KRS aktif.
                                </span>
                            </li>
                            <li>
                                <span class="public-step-number">3</span>
                                <span>
                                    <strong>Isi evaluasi</strong>
                                    Jawaban tersimpan sebagai bahan peningkatan mutu pembelajaran.
                                </span>
                            </li>
                        </ol>
                    </aside>
                @endunless

                @if ($student)
                    @php
                        $semesterName = trim((string) ($studentSemester['nama'] ?? ''));
                        $semesterLabel = $semesterName !== ''
                            ? (preg_match('/^semester\b/i', $semesterName)
                                ? $semesterName
                                : 'Semester ' . $semesterName)
                            : 'Semester ' . ($student['siakad_idsemester'] ?? '-');
                    @endphp

                    <div class="alert success">
                        Sesi mahasiswa dari SIAKAD aktif untuk tahun ajaran
                        {{ $student['siakad_idtahunajaran'] ?? '-' }} dan
                        {{ $semesterLabel }}.
                        @if (! $studentFetchError)
                            Jumlah mata kuliah dari KRS: {{ $studentSections->count() }}.
                        @endif
                    </div>

                    @if ($studentProfile)
                        <div class="student-profile" aria-label="Data mahasiswa dari SIAKAD">
                            <div class="student-profile-primary">
                                <span>Mahasiswa</span>
                                <strong>{{ $studentProfile['nama'] ?? '-' }}</strong>
                            </div>
                            <dl class="student-profile-meta">
                                <div>
                                    <dt>NPM</dt>
                                    <dd>{{ $studentProfile['npm'] ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt>ID Mahasiswa</dt>
                                    <dd>{{ $studentProfile['siakad_idmahasiswa'] ?? $student['siakad_idmahasiswa'] ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt>Tahun Ajaran</dt>
                                    <dd>{{ $student['siakad_idtahunajaran'] ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt>Semester</dt>
                                    <dd>{{ $semesterLabel }}</dd>
                                </div>
                            </dl>
                        </div>
                    @elseif ($studentProfileFetchError)
                        <div class="alert warning">{{ $studentProfileFetchError }}</div>
                    @endif

                    @if ($studentSemesterFetchError)
                        <div class="alert warning">{{ $studentSemesterFetchError }}</div>
                    @endif
                @endif

                @if ($studentFetchError)
                    <div class="alert error">{{ $studentFetchError }}</div>
                @endif

                @if (session('success'))
                    <div class="alert success">{{ session('success') }}</div>
                @endif

                @if (session('error'))
                    <div class="alert error">{{ session('error') }}</div>
                @endif
            </section>

            <section class="section">
                <div class="public-section-heading">
                    <div>
                        <h2 class="section-title">
                            {{ $student ? 'Daftar Mata Kuliah KRS' : 'EDOM Aktif' }}
                        </h2>
                        @unless ($student)
                            <p class="public-section-subtitle">
                                Instrumen evaluasi yang sedang tersedia untuk periode berjalan.
                            </p>
                        @endunless
                    </div>
                </div>

                @if ($student)
                    @forelse ($studentEdomSections as $group)
                        @php
                            $edom = $group['edom'];
                        @endphp

                        <div class="course-group">
                            <div class="course-group-heading">
                                <div>
                                    <h3>{{ $edom->name }}</h3>
                                    <p>
                                        {{ $group['sections']->count() }} mata kuliah &middot;
                                        {{ $edom->questions_count }} pernyataan dalam
                                        {{ $edom->categories_count }} kategori
                                    </p>
                                </div>
                                <span class="badge {{ ($group['period_status'] ?? '') === 'Terbuka' ? 'badge-active' : 'badge-pending' }}">
                                    {{ $group['period_status'] ?? 'Tidak Tersedia' }}
                                </span>
                            </div>

                            <ol class="course-list">
                                @foreach ($group['sections'] as $item)
                                    @php
                                        $section = $item['section'];
                                        $sectionKey = $item['section_key'] ?? '';
                                        $lecturer = is_array($section['dosen'] ?? null)
                                            ? $section['dosen']
                                            : [];
                                        $lecturerLabel = $formatLecturer($lecturer) ?? 'Belum tersedia';
                                        $teamLecturers = $sectionTeamLecturers($section);
                                    @endphp

                                    <li class="course-list-item">
                                        <div class="course-list-number">{{ $loop->iteration }}</div>

                                        <div class="course-list-content">
                                            <h2>{{ $sectionTitle($section) }}</h2>
                                            <div class="course-list-meta">
                                                <span>Dosen: {{ $lecturerLabel }}</span>
                                                @if ($teamLecturers !== [])
                                                    <span class="course-list-team">
                                                        Tim dosen: {{ implode(', ', $teamLecturers) }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="course-list-actions">
                                            <span class="badge {{ $item['completed'] ? 'badge-completed' : 'badge-pending' }}">
                                                {{ $item['completed'] ? 'Sudah Diisi' : 'Belum Diisi' }}
                                            </span>
                                            @if ($item['update_locked'] ?? false)
                                                <span class="button button-muted" aria-disabled="true">
                                                    Jawaban Terkunci
                                                </span>
                                            @else
                                                <a class="button" href="{{ route('edom.fill', [
                                                    'edomSettings' => $edom,
                                                    'section' => $sectionKey,
                                                ]) }}">
                                                    {{ $item['completed'] ? 'Perbarui Jawaban' : 'Isi EDOM' }}
                                                </a>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @empty
                        <div class="empty">
                            @if ($studentFetchError)
                                Daftar mata kuliah belum dapat dimuat dari SIAKAD.
                            @else
                                Tidak ada mata kuliah pada KRS atau belum ada EdomSettings aktif.
                            @endif
                        </div>
                    @endforelse
                @elseif ($activeEdoms->isNotEmpty())
                    <div class="grid public-edom-grid">
                        @foreach ($activeEdoms as $edom)
                            <article class="card public-edom-card">
                                <div class="public-edom-card-header">
                                    <div>
                                        <span class="badge badge-active">Aktif</span>
                                        <h2>{{ $edom->edom_name }}</h2>
                                    </div>
                                </div>

                                <p class="public-edom-programs">
                                    {{ $programStudiLabels($edom->programStudis) }}
                                </p>

                                <div class="public-edom-meta">
                                    <span>
                                        <i class="fa-solid fa-list-check"></i>
                                        {{ $edom->questions_count }} pernyataan
                                    </span>
                                    <span>
                                        <i class="fa-solid fa-layer-group"></i>
                                        {{ $edom->categories_count }} kategori
                                    </span>
                                </div>

                                <span class="button button-muted public-edom-action">Buka melalui SIAKAD</span>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="empty">
                        @if ($draftCount > 0)
                            Belum ada EdomSettings yang aktif untuk saat ini.
                        @else
                            Belum ada EdomSettings yang aktif untuk saat ini.
                        @endif
                    </div>
                @endif
            </section>

            @if ($closedEdoms->isNotEmpty())
                <section class="section">
                    <h2 class="section-title">EdomSettings yang Sudah Ditutup</h2>
                    <div class="grid">
                        @foreach ($closedEdoms as $edom)
                            <article class="card">
                                <span class="badge badge-closed">Ditutup</span>
                                <h2>{{ $edom->edom_name }}</h2>
                                <p class="meta">{{ $programStudiLabels($edom->programStudis) }}</p>
                                <span class="button button-muted">Pengisian Ditutup</span>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </main>
@endsection
