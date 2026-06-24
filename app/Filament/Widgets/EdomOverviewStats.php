<?php

namespace App\Filament\Widgets;

use App\Models\SettingsEdom;
use App\Models\EdomQuestionCategory;
use App\Models\EdomQuestion;
use App\Models\EdomResponse;
use App\Models\Course;
use App\Models\ProgramStudi;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EdomOverviewStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total EDOM', SettingsEdom::query()->count())
                ->description('Semua EDOM yang sudah dibuat')
                ->color('primary'),

            Stat::make('Draft', SettingsEdom::query()->where('status', 'draft')->count())
                ->description('EDOM yang masih disusun')
                ->color('gray'),

            Stat::make('Aktif', SettingsEdom::query()->where('status', 'aktif')->count())
                ->description('EDOM yang sedang berjalan')
                ->color('success'),

            Stat::make('Ditutup', SettingsEdom::query()->where('status', 'ditutup')->count())
                ->description('EDOM yang sudah selesai')
                ->color('danger'),

            Stat::make('Total Hasil Masuk', EdomResponse::query()->count())
                ->description('Jumlah pengisian EDOM dari mahasiswa')
                ->color('success'),

            Stat::make('Total Pertanyaan', EdomQuestion::query()->count())
                ->description('Jumlah pertanyaan evaluasi')
                ->color('info'),

            Stat::make('Total Kategori', EdomQuestionCategory::query()->count())
                ->description('Kelompok penilaian EDOM')
                ->color('warning'),

            Stat::make('Total Prodi', ProgramStudi::query()->count())
                ->description('Program studi yang terdaftar')
                ->color('success'),

            Stat::make('Total Mata Kuliah', Course::query()->count())
                ->description('Mata kuliah yang terdaftar')
                ->color('primary'),
        ];
    }
}
