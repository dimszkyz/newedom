<?php

namespace App\Filament\Resources\EdomSettings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EdomSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama EdomSettings')->searchable(),
                TextColumn::make('programStudis.nama')->label('Program Studi')->badge()->separator(),
                TextColumn::make('categories_count')->counts('categories')->label('Kategori')->badge(),
                TextColumn::make('questions_count')->counts('questions')->label('Pertanyaan')->badge(),
                TextColumn::make('responses_count')->counts('responses')->label('Hasil')->badge()->color('success'),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Aktif',
                        'closed' => 'Ditutup',
                    ]),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
