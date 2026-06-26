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

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['question.category', 'questionOption'])->orderBy('id'))
            ->columns([
                TextColumn::make('category_name_snapshot')
                    ->label('Kategori')
                    ->state(fn (EdomResponseDetail $record): string => $record->category_name_snapshot ?: ($record->question?->category?->category_name ?? 'Kategori dihapus'))
                    ->badge()
                    ->wrap(),
                TextColumn::make('statement_snapshot')
                    ->label('Pernyataan')
                    ->state(fn (EdomResponseDetail $record): string => $record->statement_snapshot ?: ($record->question?->statement ?? 'Pernyataan dihapus'))
                    ->limit(120)
                    ->wrap()
                    ->searchable(),
                TextColumn::make('option_label_snapshot')
                    ->label('Pilihan')
                    ->state(fn (EdomResponseDetail $record): string => $record->option_label_snapshot ?: ($record->questionOption?->label ?? '-'))
                    ->badge(),
                TextColumn::make('score')
                    ->label('Nilai')
                    ->state(fn (EdomResponseDetail $record): ?int => $record->option_score_snapshot ?? $record->score)
                    ->placeholder('-')
                    ->badge(),
                TextColumn::make('answer_text')->label('Jawaban Teks')->placeholder('-')->limit(120)->wrap(),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
