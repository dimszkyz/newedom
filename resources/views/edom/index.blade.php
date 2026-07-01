@extends('layouts.app')

@section('title', 'EDOM Universitas Ngudi Waluyo')

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
    @endphp

    <main class="page">
        <div class="container">
            <section class="hero">
                <p class="eyebrow">Evaluasi Dosen Oleh Mahasiswa</p>
                <h1>EDOM Universitas Ngudi Waluyo</h1>

                @if ($student)
                    @php
                        $semesterName = trim((string) ($studentSemester['nama'] ?? ''));
                        $semesterLabel = $semesterName !== ''
                            ? $semesterName
                            : 'Semester ' . ($student['siakad_idsemester'] ?? '-');
                    @endphp

                    <div class="alert success">
                        Sesi mahasiswa dari SIAKAD aktif untuk tahun ajaran
                        {{ $student['siakad_idtahunajaran'] ?? '-' }} dan semester
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
                <h2 class="section-title">
                    {{ $student ? 'Daftar Mata Kuliah KRS' : 'EdomSettings Aktif' }}
                </h2>

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
                                        {{ $edom->questions_count }} pernyataan dalam
                                        {{ $edom->categories_count }} kategori
                                    </p>
                                </div>
                                <span class="badge badge-active">Aktif</span>
                            </div>

                            <ol class="course-list">
                                @foreach ($group['sections'] as $item)
                                    @php
                                        $section = $item['section'];
                                        $sectionKey = $item['section_key'] ?? '';
                                        $lecturer = is_array($section['dosen'] ?? null)
                                            ? $section['dosen']
                                            : [];
                                    @endphp

                                    <li class="course-list-item">
                                        <div class="course-list-number">{{ $loop->iteration }}</div>

                                        <div class="course-list-content">
                                            <h2>
                                                {{ $section['kode'] ?? '-' }} -
                                                {{ $section['nama'] ?? 'Mata kuliah tanpa nama' }}
                                            </h2>
                                            <div class="course-list-meta">
                                                <span>
                                                    Dosen: {{ $lecturer['nama'] ?? '-' }}
                                                    @if (! empty($lecturer['nidn']))
                                                        ({{ $lecturer['nidn'] }})
                                                    @endif
                                                </span>
                                                <span>ID Mata Kuliah: {{ $section['idmatakuliah'] ?? '-' }}</span>
                                                <span>
                                                    ID Detail Penawaran:
                                                    {{ $section['idtawarmatakuliahdetail'] ?? '-' }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="course-list-actions">
                                            <span class="badge {{ $item['completed'] ? 'badge-completed' : 'badge-pending' }}">
                                                {{ $item['completed'] ? 'Sudah Diisi' : 'Belum Diisi' }}
                                            </span>
                                            <a class="button" href="{{ route('edom.fill', [
                                                'edomSettings' => $edom,
                                                'section' => $sectionKey,
                                            ]) }}">
                                                {{ $item['completed'] ? 'Perbarui Jawaban' : 'Isi EDOM' }}
                                            </a>
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
                    <div class="grid">
                        @foreach ($activeEdoms as $edom)
                            <article class="card">
                                <span class="badge badge-active">Aktif</span>
                                <h2>{{ $edom->edom_name }}</h2>
                                <p class="meta">
                                    {{ $edom->programStudis->pluck('nama')->join(', ') ?: 'Semua Program Studi' }}<br>
                                    {{ $edom->questions_count }} pernyataan dalam {{ $edom->categories_count }} kategori
                                </p>
                                <span class="button button-muted">Buka melalui SIAKAD</span>
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
                                <p class="meta">{{ $edom->programStudis->pluck('nama')->join(', ') ?: 'Semua Program Studi' }}</p>
                                <span class="button button-muted">Pengisian Ditutup</span>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </main>
@endsection
