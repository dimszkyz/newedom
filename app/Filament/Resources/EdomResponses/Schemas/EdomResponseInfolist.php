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
                    TextEntry::make('settingEdom.name')->label('Setting EDOM')->placeholder('-'),
                    TextEntry::make('period.year')->label('Tahun Ajaran')->placeholder('-'),
                    TextEntry::make('period.siakad_idsemester')->label('Semester')->placeholder('-'),
                    TextEntry::make('siakad_idmahasiswa')->label('ID Mahasiswa')->placeholder('-'),
                    TextEntry::make('siakad_idmatakuliah')->label('ID Mata Kuliah')->placeholder('-'),
                    TextEntry::make('siakad_idtawarmatakuliahdetail')->label('ID Detail Penawaran')->placeholder('-'),
                    TextEntry::make('submitted_at')->label('Dikirim')->dateTime('d M Y H:i'),
                    TextEntry::make('details_count')->label('Jumlah Jawaban')->state(fn (EdomResponse $record): int => $record->details()->count())->badge(),
                ])
                ->columns(2),
        ]);
    }
}
