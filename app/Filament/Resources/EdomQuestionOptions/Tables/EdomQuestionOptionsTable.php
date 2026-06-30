<?php

namespace App\Filament\Resources\EdomQuestionOptions\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EdomQuestionOptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('edomSettings.name')->label('EdomSettings')->searchable(),
                TextColumn::make('name')->label('Opsi Jawaban')->searchable(),
                TextColumn::make('score')->label('Nilai')->sortable(),
            ])
            ->defaultSort('score');
    }
}
