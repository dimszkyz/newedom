<?php

namespace App\Filament\Resources\EdomResponses\RelationManagers;

use App\Models\EdomResponseDetail;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'details';

    protected static ?string $title = 'Detail Jawaban';

    protected static ?string $modelLabel = 'Jawaban';

    protected static ?string $pluralModelLabel = 'Detail Jawaban';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['question.category', 'option'])->orderBy('id'))
            ->columns([
                TextColumn::make('question.category.name')
                    ->label('Kategori')
                    ->placeholder('Kategori dihapus')
                    ->badge()
                    ->color('primary')
                    ->wrap(),

                TextColumn::make('question.statement')
                    ->label('Pernyataan')
                    ->placeholder('Pernyataan dihapus')
                    ->limit(120)
                    ->wrap()
                    ->searchable(),

                TextColumn::make('option.name')
                    ->label('Pilihan')
                    ->state(fn (EdomResponseDetail $record): string => $record->option?->name ?: (filled($record->answer_text) ? '-' : '-'))
                    ->badge()
                    ->color(fn (EdomResponseDetail $record): string => $record->option ? 'success' : 'gray'),

                TextColumn::make('score')
                    ->label('Nilai')
                    ->state(fn (EdomResponseDetail $record): ?int => $record->option?->score)
                    ->placeholder('-')
                    ->badge()
                    ->color('success'),

                TextColumn::make('answer_text')
                    ->label('Jawaban Teks')
                    ->placeholder('-')
                    ->limit(120)
                    ->wrap(),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
