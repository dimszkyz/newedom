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
            ->modifyQueryUsing(fn ($query) => $query
                ->with(['edom', 'period', 'details.option'])
                ->withCount('details')
                ->latest('submitted_at')
                ->latest('id'))
            ->columns([
                TextColumn::make('edom.name')
                    ->label('EDOM')
                    ->placeholder('EDOM dihapus')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('period.display_name')
                    ->label('Periode')
                    ->placeholder('-'),

                TextColumn::make('siakad_idmahasiswa')
                    ->label('ID Mahasiswa')
                    ->searchable(),

                TextColumn::make('siakad_idmatakuliah')
                    ->label('ID Mata Kuliah')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('siakad_idtawarmatakuliahdetail')
                    ->label('ID Detail Penawaran')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('details_count')
                    ->counts('details')
                    ->label('Jawaban')
                    ->badge(),

                TextColumn::make('average_score')
                    ->label('Rata-rata Nilai')
                    ->state(function (EdomResponse $record): string {
                        $scores = $record->details
                            ->map(fn ($detail) => $detail->option?->score)
                            ->filter(fn ($score) => $score !== null);

                        $average = $scores->isEmpty() ? null : $scores->avg();

                        return $average === null ? '-' : number_format((float) $average, 2, ',', '.');
                    })
                    ->badge()
                    ->color('success'),

                TextColumn::make('submitted_at')
                    ->label('Dikirim')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('edom')
                    ->label('EDOM')
                    ->relationship('edom', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Semua EDOM'),

                SelectFilter::make('period')
                    ->label('Periode')
                    ->relationship('period', 'year')
                    ->searchable()
                    ->preload()
                    ->placeholder('Semua periode'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Lihat Hasil'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
