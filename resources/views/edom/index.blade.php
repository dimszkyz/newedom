@extends('layouts.app')

@section('title', 'EDOM Universitas Ngudi Waluyo')

@section('content')
    @php
        $student = $student ?? null;
        $studentSections = collect($studentSections ?? []);
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
                <h2 class="section-title">Setting EDOM Aktif</h2>

                @if ($activeEdoms->isNotEmpty())
                    <div class="grid">
                        @foreach ($activeEdoms as $edom)
                            <article class="card">
                                <span class="badge badge-active">Aktif</span>
                                <h2>{{ $edom->edom_name }}</h2>
                                <p class="meta">
                                    {{ $edom->programStudis->pluck('nama')->join(', ') ?: 'Semua Program Studi' }}<br>
                                    {{ $edom->questions_count }} pernyataan dalam {{ $edom->categories_count }} kategori
                                </p>
                                <a class="button" href="{{ route('edom.home', ['edom' => $edom->id]) }}">
                                    {{ $student ? 'Isi EDOM dari KRS' : 'Isi EDOM' }}
                                </a>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="empty">
                        @if ($student && ! $studentFetchError)
                            Tidak ada setting EDOM aktif yang cocok dengan program studi pada KRS Anda.
                        @elseif ($draftCount > 0)
                            Belum ada setting EDOM yang aktif untuk saat ini.
                        @else
                            Belum ada setting EDOM yang aktif untuk saat ini.
                        @endif
                    </div>
                @endif
            </section>

            @if ($closedEdoms->isNotEmpty())
                <section class="section">
                    <h2 class="section-title">Setting EDOM yang Sudah Ditutup</h2>
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
