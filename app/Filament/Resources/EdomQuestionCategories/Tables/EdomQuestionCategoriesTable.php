<?php

namespace App\Filament\Resources\EdomQuestionCategories\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EdomQuestionCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('edomSettings.name')->label('EdomSettings')->searchable()->sortable(),
            TextColumn::make('name')->label('Kategori')->searchable()->sortable(),
            TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
        ]);
    }
}
