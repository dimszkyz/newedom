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
                TextColumn::make('category_name_for_display')
                    ->label('Kategori')
                    ->state(fn (EdomResponseDetail $record): string => $record->category_name_for_display)
                    ->badge()
                    ->wrap(),
                TextColumn::make('question_statement_for_display')
                    ->label('Pernyataan')
                    ->state(fn (EdomResponseDetail $record): string => $record->question_statement_for_display)
                    ->limit(120)
                    ->wrap(),
                TextColumn::make('option_name_for_display')
                    ->label('Pilihan')
                    ->state(fn (EdomResponseDetail $record): ?string => $record->option_name_for_display)
                    ->placeholder('-')
                    ->badge(),
                TextColumn::make('option_score_for_display')
                    ->label('Nilai')
                    ->state(fn (EdomResponseDetail $record): ?int => $record->option_score_for_display)
                    ->placeholder('-')
                    ->badge()
                    ->color('success'),
                TextColumn::make('answer_text')->label('Jawaban Teks')->placeholder('-')->limit(120)->wrap(),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
