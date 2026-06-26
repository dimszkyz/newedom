<?php

namespace App\Filament\Resources\SettingEdoms\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SettingEdomsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama Setting EDOM')->searchable(),
                TextColumn::make('programStudis.name')->label('Program Studi')->badge()->separator(),
                TextColumn::make('categories_count')->counts('categories')->label('Kategori')->badge(),
                TextColumn::make('questions_count')->counts('questions')->label('Pertanyaan')->badge(),
                TextColumn::make('responses_count')->counts('responses')->label('Hasil')->badge()->color('success'),
                TextColumn::make('status')->label('Status')->badge()->color(fn (string $state): string => match ($state) {
                    'draft' => 'gray',
                    'active' => 'success',
                    'closed' => 'danger',
                    default => 'gray',
                }),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(['draft' => 'Draft', 'active' => 'Aktif', 'closed' => 'Ditutup'])
                    ->placeholder('Semua status'),

                SelectFilter::make('programStudis')
                    ->label('Program Studi')
                    ->relationship('programStudis', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Semua program studi'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
