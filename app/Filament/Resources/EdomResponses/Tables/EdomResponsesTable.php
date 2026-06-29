<?php

namespace App\Filament\Resources\EdomResponses\Tables;

use App\Models\EdomResponse;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EdomResponsesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['settingEdom', 'details.questionOption'])->latest('submitted_at')->latest('id'))
            ->columns([
                TextColumn::make('settingEdom.name')->label('Setting EDOM')->placeholder('-')->searchable(),
                TextColumn::make('period.year')->label('Tahun')->placeholder('-'),
                TextColumn::make('period.siakad_idsemester')->label('Semester')->placeholder('-'),
                TextColumn::make('siakad_idmahasiswa')->label('ID Mahasiswa')->placeholder('-')->searchable(),
                TextColumn::make('siakad_idmatakuliah')->label('ID Mata Kuliah')->placeholder('-'),
                TextColumn::make('siakad_idtawarmatakuliahdetail')->label('ID Detail')->placeholder('-'),
                TextColumn::make('details_count')->counts('details')->label('Jawaban')->badge(),
                TextColumn::make('average_score')
                    ->label('Rata-rata Nilai')
                    ->state(function (EdomResponse $record): string {
                        $scores = $record->details
                            ->map(fn ($detail) => $detail->questionOption?->score)
                            ->filter(fn ($score) => $score !== null);

                        return $scores->isEmpty() ? '-' : number_format((float) $scores->avg(), 2, ',', '.');
                    })
                    ->badge()
                    ->color('success'),
                TextColumn::make('submitted_at')->label('Dikirim')->dateTime('d M Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('settingEdom')->label('Setting EDOM')->relationship('settingEdom', 'name')->searchable()->preload(),
            ])
            ->recordActions([ViewAction::make()->label('Lihat Hasil')])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
