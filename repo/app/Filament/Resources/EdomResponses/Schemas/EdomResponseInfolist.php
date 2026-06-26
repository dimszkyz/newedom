<?php

namespace App\Filament\Resources\EdomResponses\Schemas;

use App\Models\EdomResponse;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EdomResponseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Pengisian')
                ->schema([
                    TextEntry::make('edom_name_snapshot')->label('Setting EDOM')->state(fn (EdomResponse $record): string => $record->edom_name_snapshot ?: ($record->settingEdom?->edom_name ?? 'Setting EDOM dihapus')),
                    TextEntry::make('study_program_snapshot')->label('Program Studi')->placeholder('-'),
                    TextEntry::make('course_snapshot')->label('Mata Kuliah dari KRS')->placeholder('-'),
                    TextEntry::make('lecturer_name_snapshot')->label('Dosen')->placeholder('-'),
                    TextEntry::make('lecturer_nidn_snapshot')->label('NIDN')->placeholder('-'),
                    TextEntry::make('respondent_name')->label('Nama Mahasiswa')->placeholder('Anonim'),
                    TextEntry::make('student_number')->label('NIM')->placeholder('-'),
                    TextEntry::make('submitted_at')->label('Dikirim')->dateTime('d M Y H:i'),
                    TextEntry::make('details_count')->label('Jumlah Jawaban')->state(fn (EdomResponse $record): int => $record->details()->count())->badge(),
                    TextEntry::make('average_score')->label('Rata-rata Nilai')->state(function (EdomResponse $record): string {
                        $average = $record->details()->whereNotNull('score')->avg('score');
                        return $average === null ? '-' : number_format((float) $average, 2, ',', '.');
                    })->badge()->color('success'),
                ])->columns(2),
        ]);
    }
}
