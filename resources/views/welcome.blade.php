@extends('layouts.app')

@section('title', 'EDOM Universitas Ngudi Waluyo')

@section('content')
    <main class="page">
        <div class="container">
            <section class="hero">
                <p class="eyebrow">Evaluasi Dosen Oleh Mahasiswa</p>
                <h1>EDOM Universitas Ngudi Waluyo</h1>
                <p class="lead">
                    Silakan kembali ke halaman utama EDOM untuk melihat evaluasi yang sedang aktif.
                </p>
                <a class="button" href="{{ route('edom.home') }}">Buka Halaman EDOM</a>
            </section>
        </div>
    </main>
@endsection
