@extends('layouts.app')

@section('title', 'EDOM Universitas Ngudi Waluyo')

@section('content')
    @php
        $student = $student ?? null;
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
                    <div class="alert success">
                        Sesi mahasiswa dari SIAKAD aktif untuk tahun ajaran
                        {{ $student['siakad_idtahunajaran'] ?? '-' }} dan semester
                        {{ $student['siakad_idsemester'] ?? '-' }}.
                        @if (! $studentFetchError)
                            Jumlah mata kuliah dari KRS: {{ $studentSections->count() }}.
                        @endif
                    </div>
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

                            <div class="grid">
                                @foreach ($group['sections'] as $item)
                                    @php
                                        $section = $item['section'];
                                        $lecturer = is_array($section['dosen'] ?? null)
                                            ? $section['dosen']
                                            : [];
                                    @endphp

                                    <article class="card course-card">
                                        <span class="badge {{ $item['completed'] ? 'badge-completed' : 'badge-pending' }}">
                                            {{ $item['completed'] ? 'Sudah Diisi' : 'Belum Diisi' }}
                                        </span>
                                        <h2>
                                            {{ $section['kode'] ?? '-' }} -
                                            {{ $section['nama'] ?? 'Mata kuliah tanpa nama' }}
                                        </h2>
                                        <p class="meta">
                                            Dosen: {{ $lecturer['nama'] ?? '-' }}
                                            @if (! empty($lecturer['nidn']))
                                                ({{ $lecturer['nidn'] }})
                                            @endif
                                            <br>
                                            ID Mata Kuliah: {{ $section['idmatakuliah'] ?? '-' }}<br>
                                            ID Detail Penawaran:
                                            {{ $section['idtawarmatakuliahdetail'] ?? '-' }}
                                        </p>
                                        <a class="button" href="{{ route('edom.fill', [
                                            'edomSettings' => $edom,
                                            'section' => $section['idtawarmatakuliahdetail'] ?? '',
                                        ]) }}">
                                            {{ $item['completed'] ? 'Perbarui Jawaban' : 'Isi EDOM' }}
                                        </a>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="empty">
                            @if ($studentFetchError)
                                Daftar mata kuliah belum dapat dimuat dari SIAKAD.
                            @else
                                Tidak ada mata kuliah KRS yang cocok dengan EdomSettings aktif.
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
