@extends('layouts.app')

@section('title', $statusTitle . ' - EDOM Universitas Ngudi Waluyo')

@section('content')
    <main class="page">
        <div class="container">
            <section class="status-card">
                <span class="badge">{{ strtoupper($edom->status ?? '-') }}</span>
                <h1>{{ $statusTitle }}</h1>
                <p class="edom-name">{{ $edom->edom_name }}</p>
                <p class="message">{{ $statusMessage }}</p>
                <a class="button" href="{{ route('edom.home') }}">Kembali ke Halaman EDOM</a>
            </section>
        </div>
    </main>
@endsection
