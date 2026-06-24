<?php

namespace App\Filament\Resources\Prodis\Tables;

use App\Models\Prodi;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProdisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('unw_study_program_id')
                    ->label('ID API')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('name')
                    ->label('Nama Prodi')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('degree_short_name')
                    ->label('Jenjang')
                    ->badge()
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('faculty_name')
                    ->label('Fakultas')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('api_updated_at')
                    ->label('Update API')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('synced_at')
                    ->label('Terakhir Sync')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('degree_short_name')
                    ->label('Jenjang')
                    ->options(fn (): array => Prodi::query()
                        ->whereNotNull('degree_short_name')
                        ->distinct()
                        ->orderBy('degree_short_name')
                        ->pluck('degree_short_name', 'degree_short_name')
                        ->all())
                    ->placeholder('Semua jenjang'),

                SelectFilter::make('faculty_name')
                    ->label('Fakultas')
                    ->options(fn (): array => Prodi::query()
                        ->whereNotNull('faculty_name')
                        ->distinct()
                        ->orderBy('faculty_name')
                        ->pluck('faculty_name', 'faculty_name')
                        ->all())
                    ->placeholder('Semua fakultas'),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
