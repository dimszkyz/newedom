<?php

namespace App\Filament\Resources\EdomQuestionOptions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EdomQuestionOptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('settingEdom.name')->label('Setting EDOM')->searchable(),
                TextColumn::make('sort_order')->label('Urutan')->sortable(),
                TextColumn::make('name')->label('Opsi Jawaban')->searchable(),
                TextColumn::make('score')->label('Nilai')->sortable(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
            ])
            ->defaultSort('sort_order')
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
