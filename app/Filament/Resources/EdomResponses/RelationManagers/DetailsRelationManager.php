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
                TextColumn::make('question.category.name')->label('Kategori')->badge()->wrap(),
                TextColumn::make('question.statement')->label('Pernyataan')->limit(120)->wrap()->searchable(),
                TextColumn::make('questionOption.name')->label('Pilihan')->placeholder('-')->badge(),
                TextColumn::make('questionOption.score')->label('Nilai')->placeholder('-')->badge()->color('success'),
                TextColumn::make('answer_text')->label('Jawaban Teks')->placeholder('-')->limit(120)->wrap(),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
