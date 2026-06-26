<?php

namespace App\Filament\Resources\Edoms\RelationManagers;

use App\Filament\Resources\EdomResponses\EdomResponseResource;
use App\Models\EdomResponse;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ResponsesRelationManager extends RelationManager
{
    protected static string $relationship = 'responses';

    protected static ?string $title = 'Hasil Pengisian';

    protected static ?string $modelLabel = 'Hasil Pengisian';

    protected static ?string $pluralModelLabel = 'Hasil Pengisian';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->with(['details.option', 'period'])
                ->withCount('details')
                ->latest('submitted_at')
                ->latest('id'))
            ->columns([
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
            ->recordActions([
                ViewAction::make()
                    ->label('Lihat Hasil')
                    ->url(fn (EdomResponse $record): string => EdomResponseResource::getUrl('view', ['record' => $record])),
            ])
            ->toolbarActions([]);
    }
}
