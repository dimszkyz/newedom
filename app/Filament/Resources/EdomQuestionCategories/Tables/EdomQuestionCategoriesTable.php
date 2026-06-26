<?php

namespace App\Filament\Resources\EdomQuestionCategories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EdomQuestionCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('edom.name')->label('EDOM')->searchable()->sortable(),
                TextColumn::make('name')->label('Kategori')->searchable()->sortable(),
                TextColumn::make('questions_count')->counts('questions')->label('Pertanyaan')->badge(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
