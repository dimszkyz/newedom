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
                        TextEntry::make('edom.name')
                            ->label('EDOM')
                            ->placeholder('EDOM dihapus'),

                        TextEntry::make('period.display_name')
                            ->label('Periode')
                            ->placeholder('-'),

                        TextEntry::make('siakad_idmahasiswa')
                            ->label('ID Mahasiswa')
                            ->placeholder('-'),

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
                                    ->join('edom_question_options', 'edom_response_detail.edom_option_id', '=', 'edom_question_options.id')
                                    ->avg('edom_question_options.score');

                                return $average === null ? '-' : number_format((float) $average, 2, ',', '.');
                            })
                            ->badge()
                            ->color('success'),
                    ])
                    ->columns(2),
            ]);
    }
}
