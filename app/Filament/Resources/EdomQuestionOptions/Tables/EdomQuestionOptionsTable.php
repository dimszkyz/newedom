<?php

namespace App\Filament\Resources\EdomQuestionOptions\Tables;

use App\Models\EdomQuestionOption;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EdomQuestionOptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['edomSettings']))
            ->columns([
                TextColumn::make('edomSettings.name')->label('EdomSettings')->searchable(),
                TextColumn::make('name')->label('Opsi Jawaban')->searchable(),
                TextColumn::make('score')->label('Nilai')->sortable(),
                TextColumn::make('lock_info')
                    ->label('Keterangan')
                    ->state(fn (EdomQuestionOption $record): string => $record->edomSettings?->questionMasterLockLabel() ?? 'Terkunci')
                    ->badge()
                    ->color(fn (EdomQuestionOption $record): string => $record->edomSettings?->canModifyQuestionMaster() ? 'success' : 'warning'),
            ])
            ->defaultSort('score');
    }
}
