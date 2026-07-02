<?php

namespace App\Filament\Resources\EdomResponses\Schemas;

use App\Models\EdomResponse;
use App\Services\Edom\EdomResponseMetadata;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EdomResponseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Mahasiswa')
                ->schema([
                    TextEntry::make('student_name')
                        ->label('Nama Mahasiswa')
                        ->state(fn (EdomResponse $record): string => app(EdomResponseMetadata::class)->studentNameFor($record)),
                    TextEntry::make('student_nim')
                        ->label('NIM')
                        ->state(fn (EdomResponse $record): string => app(EdomResponseMetadata::class)->studentNimFor($record))
                        ->placeholder('-'),
                    TextEntry::make('siakad_idmahasiswa')
                        ->label('ID Mahasiswa SIAKAD')
                        ->placeholder('-'),
                    TextEntry::make('semester_label')
                        ->label('Semester')
                        ->state(fn (EdomResponse $record): string => app(EdomResponseMetadata::class)->semesterNameFor($record))
                        ->badge(),
                    TextEntry::make('tahun_ajaran')
                        ->label('Tahun Ajaran')
                        ->state(fn (EdomResponse $record): string => app(EdomResponseMetadata::class)->tahunAjaranFor($record))
                        ->badge(),
                    TextEntry::make('submitted_at')
                        ->label('Waktu Submit')
                        ->dateTime('d M Y H:i'),
                ])
                ->columns(3),

            Section::make('Informasi EDOM Mata Kuliah')
                ->schema([
                    TextEntry::make('edomSettings.name')
                        ->label('Nama EDOM')
                        ->placeholder('-'),
                    TextEntry::make('course_label')
                        ->label('Mata Kuliah')
                        ->state(fn (EdomResponse $record): string => app(EdomResponseMetadata::class)->courseLabelFor($record)),
                    TextEntry::make('lecturer_name')
                        ->label('Dosen')
                        ->state(fn (EdomResponse $record): string => app(EdomResponseMetadata::class)->dosenNameFor($record))
                        ->placeholder('-'),
                    TextEntry::make('lecturer_team')
                        ->label('Tim Dosen')
                        ->state(fn (EdomResponse $record): string => app(EdomResponseMetadata::class)->dosenTeamFor($record) ?: '-')
                        ->placeholder('-'),
                    TextEntry::make('siakad_idmatakuliah')
                        ->label('ID Mata Kuliah')
                        ->placeholder('-'),
                    TextEntry::make('siakad_idtawarmatakuliahdetail')
                        ->label('ID Detail Penawaran')
                        ->placeholder('-'),
                    TextEntry::make('details_count')
                        ->label('Jumlah Jawaban')
                        ->state(fn (EdomResponse $record): int => $record->details()->count())
                        ->badge(),
                    TextEntry::make('average_score')
                        ->label('Rata-rata Nilai')
                        ->state(fn (EdomResponse $record): string => app(EdomResponseMetadata::class)->formattedAverageScoreFor($record))
                        ->badge(),
                ])
                ->columns(2),
        ]);
    }
}
