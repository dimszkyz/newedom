<?php

namespace App\Filament\Resources\ProgramStudis\Tables;

use App\Models\ProgramStudi;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProgramStudisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('id_unw_program_studi')->label('ID API')->sortable()->toggleable(),
                TextColumn::make('name')->label('Nama Program Studi')->searchable()->sortable(),
                TextColumn::make('degree_short_name')->label('Jenjang')->badge()->placeholder('-')->sortable(),
                TextColumn::make('faculty_name')->label('Fakultas')->placeholder('-')->searchable()->sortable()->wrap(),
                TextColumn::make('slug')->label('Slug')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('api_updated_at')->label('Update API')->dateTime('d M Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('synced_at')->label('Terakhir Sync')->dateTime('d M Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('degree_short_name')
                    ->label('Jenjang')
                    ->options(fn (): array => ProgramStudi::query()->whereNotNull('degree_short_name')->distinct()->orderBy('degree_short_name')->pluck('degree_short_name', 'degree_short_name')->all())
                    ->placeholder('Semua jenjang'),
                SelectFilter::make('faculty_name')
                    ->label('Fakultas')
                    ->options(fn (): array => ProgramStudi::query()->whereNotNull('faculty_name')->distinct()->orderBy('faculty_name')->pluck('faculty_name', 'faculty_name')->all())
                    ->placeholder('Semua fakultas'),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
