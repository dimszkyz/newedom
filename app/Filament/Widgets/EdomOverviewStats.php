<?php

namespace App\Filament\Widgets;

use App\Models\Edom;
use App\Models\EdomCategory;
use App\Models\EdomQuestion;
use App\Models\EdomResponse;
use App\Models\MataKuliah;
use App\Models\Prodi;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EdomOverviewStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total EDOM', Edom::query()->count())
                ->description('Semua EDOM yang sudah dibuat')
                ->color('primary'),

            Stat::make('Draft', Edom::query()->where('status', 'draft')->count())
                ->description('EDOM yang masih disusun')
                ->color('gray'),

            Stat::make('Aktif', Edom::query()->where('status', 'aktif')->count())
                ->description('EDOM yang sedang berjalan')
                ->color('success'),

            Stat::make('Ditutup', Edom::query()->where('status', 'ditutup')->count())
                ->description('EDOM yang sudah selesai')
                ->color('danger'),

            Stat::make('Total Hasil Masuk', EdomResponse::query()->count())
                ->description('Jumlah pengisian EDOM dari mahasiswa')
                ->color('success'),

            Stat::make('Total Pertanyaan', EdomQuestion::query()->count())
                ->description('Jumlah pertanyaan evaluasi')
                ->color('info'),

            Stat::make('Total Kategori', EdomCategory::query()->count())
                ->description('Kelompok penilaian EDOM')
                ->color('warning'),

            Stat::make('Total Prodi', Prodi::query()->count())
                ->description('Program studi yang terdaftar')
                ->color('success'),

            Stat::make('Total Mata Kuliah', MataKuliah::query()->count())
                ->description('Mata kuliah yang terdaftar')
                ->color('primary'),
        ];
    }
}
