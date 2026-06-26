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
        return $schema
            ->components([
                Section::make('Informasi Pengisian')
                    ->schema([
                        TextEntry::make('settingEdom.name')
                            ->label('Setting EDOM')
                            ->placeholder('-'),

                        TextEntry::make('period.label')
                            ->label('Periode')
                            ->placeholder('-'),

                        TextEntry::make('siakad_idmahasiswa')
                            ->label('ID Mahasiswa'),

                        TextEntry::make('siakad_idmatakuliah')
                            ->label('ID Mata Kuliah')
                            ->placeholder('-'),

                        TextEntry::make('siakad_idtawarmatakuliahdetail')
                            ->label('ID Detail Penawaran')
                            ->placeholder('-'),

                        TextEntry::make('submitted_at')
                            ->label('Dikirim')
                            ->dateTime('d M Y H:i'),

                        TextEntry::make('details_count')
                            ->label('Jumlah Jawaban')
                            ->state(fn (EdomResponse $record): int => $record->details()->count())
                            ->badge(),

                        TextEntry::make('average_score')
                            ->label('Rata-rata Nilai')
                            ->state(function (EdomResponse $record): string {
                                $average = $record->details()
                                    ->with('questionOption')
                                    ->get()
                                    ->map(fn ($detail) => $detail->questionOption?->score)
                                    ->filter(fn ($score) => $score !== null)
                                    ->avg();

                                return $average === null ? '-' : number_format((float) $average, 2, ',', '.');
                            })
                            ->badge()
                            ->color('success'),
                    ])
                    ->columns(2),
            ]);
    }
}
