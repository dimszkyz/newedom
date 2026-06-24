<?php

namespace App\Filament\Resources\Edoms\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EdomsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('edom_name')
                    ->label('Nama EDOM')
                    ->searchable(),

                TextColumn::make('prodis.name')
                    ->label('Prodi')
                    ->badge()
                    ->separator(),

                TextColumn::make('mataKuliahs.name')
                    ->label('Mata Kuliah')
                    ->badge()
                    ->color('primary')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->searchable(),

                TextColumn::make('categories_count')
                    ->counts('categories')
                    ->label('Kategori')
                    ->badge(),

                TextColumn::make('questions_count')
                    ->counts('questions')
                    ->label('Pertanyaan')
                    ->badge(),

                TextColumn::make('responses_count')
                    ->counts('responses')
                    ->label('Hasil')
                    ->badge()
                    ->color('success'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'active' => 'success',
                        'closed' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Aktif',
                        'closed' => 'Ditutup',
                    ])
                    ->placeholder('Semua status'),

                SelectFilter::make('prodis')
                    ->label('Prodi')
                    ->relationship('prodis', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Semua prodi'),

                SelectFilter::make('mataKuliahs')
                    ->label('Mata Kuliah')
                    ->relationship('mataKuliahs', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Semua mata kuliah'),
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
