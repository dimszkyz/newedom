<?php

namespace App\Filament\Resources\EdomQuestions\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EdomQuestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('settingEdom.name')
                    ->label('Setting EDOM')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('statement')
                    ->label('Pernyataan')
                    ->limit(60)
                    ->searchable(),

                TextColumn::make('question_type')
                    ->label('Tipe')
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i'),
            ]);
    }
}
