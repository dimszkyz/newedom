<?php

namespace App\Filament\Resources\ProgramStudis\Tables;

use App\Models\ProgramStudi;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProgramStudisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('nama')
            ->columns([
                TextColumn::make('id_unw_program_studi')
                    ->label('id')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('display_name')
                    ->label('Program Studi')
                    ->state(fn (ProgramStudi $record): string => $record->display_name)
                    ->searchable(['nama', 'jenjang', 'jenjang_nama_singkat'])
                    ->sortable(query: fn ($query, string $direction) => $query
                        ->orderBy('jenjang_nama_singkat', $direction)
                        ->orderBy('nama', $direction)),

                TextColumn::make('nama_fakultas')
                    ->label('Fakultas')
                    ->placeholder('-')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('synced_at')
                    ->label('Terakhir Sinkron')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
