@extends('layouts.app')

@section('title', 'EDOM Universitas Ngudi Waluyo')

@section('content')
    <main class="page">
        <div class="container">
            <section class="hero">
                <p class="eyebrow">Evaluasi Dosen Oleh Mahasiswa</p>
                <h1>EDOM Universitas Ngudi Waluyo</h1>


                @if (session('success'))
                    <div class="alert success">{{ session('success') }}</div>
                @endif

                @if (session('error'))
                    <div class="alert error">{{ session('error') }}</div>
                @endif
            </section>

            <section class="section">
                <h2 class="section-title">EDOM Aktif</h2>

                @if ($activeEdoms->isNotEmpty())
                    <div class="grid">
                        @foreach ($activeEdoms as $edom)
                            <article class="card">
                                <span class="badge badge-active">Aktif</span>
                                <h2>{{ $edom->edom_name }}</h2>
                                <p class="meta">
                                    {{ $edom->prodis->pluck('name')->join(', ') ?: 'Semua Prodi' }}<br>
                                    {{ $edom->mataKuliahs->pluck('name')->join(', ') ?: 'Semua Mata Kuliah' }}<br>
                                    {{ $edom->questions_count }} pernyataan dalam {{ $edom->categories_count }} kategori
                                </p>
                                <a class="button" href="{{ route('edom.home', ['edom' => $edom->id]) }}">Isi EDOM</a>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="empty">
                        @if ($draftCount > 0)
                            <br>Belum ada EDOM yang aktif untuk saat ini, harap tunggu dan kembali beberapa saat lagi.
                        @endif
                    </div>
                @endif
            </section>

            @if ($closedEdoms->isNotEmpty())
                <section class="section">
                    <h2 class="section-title">EDOM yang Sudah Ditutup</h2>
                    <div class="grid">
                        @foreach ($closedEdoms as $edom)
                            <article class="card">
                                <span class="badge badge-closed">Ditutup</span>
                                <h2>{{ $edom->edom_name }}</h2>
                                <p class="meta">
                                    {{ $edom->prodis->pluck('name')->join(', ') ?: 'Semua Prodi' }}<br>
                                    {{ $edom->mataKuliahs->pluck('name')->join(', ') ?: 'Semua Mata Kuliah' }}
                                </p>
                                <span class="button button-muted">Pengisian Ditutup</span>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </main>
@endsection
