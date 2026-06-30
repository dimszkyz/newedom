<?php

namespace App\Filament\Widgets;

use App\Models\EdomQuestion;
use App\Models\EdomQuestionCategory;
use App\Models\EdomResponse;
use App\Models\EdomSettings;
use App\Models\ProgramStudi;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EdomOverviewStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total EdomSettings', EdomSettings::query()->count())->description('Semua EdomSettings yang sudah dibuat')->color('primary'),
            Stat::make('Draft', EdomSettings::query()->where('status', 'draft')->count())->description('EdomSettings yang masih disusun')->color('gray'),
            Stat::make('Aktif', EdomSettings::query()->where('status', 'active')->count())->description('EdomSettings yang sedang berjalan')->color('success'),
            Stat::make('Ditutup', EdomSettings::query()->where('status', 'closed')->count())->description('EdomSettings yang sudah selesai')->color('danger'),
            Stat::make('Total Hasil Masuk', EdomResponse::query()->count())->description('Jumlah pengisian EDOM dari mahasiswa')->color('success'),
            Stat::make('Total Pertanyaan', EdomQuestion::query()->count())->description('Jumlah pertanyaan evaluasi')->color('info'),
            Stat::make('Total Kategori', EdomQuestionCategory::query()->count())->description('Kelompok penilaian EDOM')->color('warning'),
            Stat::make('Total Program Studi', ProgramStudi::query()->count())->description('Program studi yang terdaftar')->color('success'),
        ];
    }
}
