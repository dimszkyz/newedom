<?php

namespace App\Filament\Resources\Prodis\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProdisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('nama')
            ->columns([
                TextColumn::make('id_unw_program_studi')
                    ->label('ID API')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('nama')
                    ->label('Nama Prodi')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
