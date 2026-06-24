<?php

namespace App\Filament\Resources\MataKuliahs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Grouping\Group;

class MataKuliahsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('prodi.name')
                    ->label('Prodi')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Mata Kuliah')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->groups([
                Group::make('prodi.name')
                    ->label('Program Studi')
                    ->collapsible(),
            ])
            ->defaultGroup('prodi.name');
    }
}
