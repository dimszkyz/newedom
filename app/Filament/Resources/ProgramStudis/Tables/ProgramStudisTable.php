<?php

namespace App\Filament\Resources\ProgramStudis\Tables;

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
                    ->label('ID API UNW')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('jenjang_nama_singkat')
                    ->label('Jenjang')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('nama')
                    ->label('Program Studi')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('unw_fakultas_nama')
                    ->label('Fakultas')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('updated_at')
                    ->label('Terakhir Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
