<?php

namespace App\Filament\Resources\EdomQuestions\Tables;

use App\Models\EdomQuestion;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EdomQuestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['edomSettings']))
            ->columns([
                TextColumn::make('category.name')->label('Kategori')->searchable()->sortable(),
                TextColumn::make('statement')->label('Pernyataan')->limit(60)->searchable(),
                TextColumn::make('question_type')->label('Tipe')->badge(),
                TextColumn::make('lock_info')
                    ->label('Keterangan')
                    ->state(fn (EdomQuestion $record): string => $record->edomSettings?->isDraft()
                        ? 'Bisa diubah'
                        : 'Dikunci karena status EDOM Settings Aktif atau Ditutup')
                    ->badge()
                    ->color(fn (EdomQuestion $record): string => $record->edomSettings?->isDraft() ? 'success' : 'warning'),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (EdomQuestion $record): bool => $record->edomSettings?->isDraft() ?? false),
            ])
            ->toolbarActions([]);
    }
}
