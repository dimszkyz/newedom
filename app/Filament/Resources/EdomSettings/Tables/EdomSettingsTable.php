<?php

namespace App\Filament\Resources\EdomSettings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EdomSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('programStudis'))
            ->columns([
                TextColumn::make('name')->label('Nama EdomSettings')->searchable(),
                TextColumn::make('program_studi_preview')
                    ->label('Program Studi')
                    ->state(function ($record): string {
                        $programStudis = $record->programStudis
                            ->map(fn ($programStudi): string => $programStudi->display_name)
                            ->filter()
                            ->values();

                        if ($programStudis->isEmpty()) {
                            return '-';
                        }

                        if ($programStudis->count() <= 3) {
                            return $programStudis->join(', ');
                        }

                        return $programStudis->take(3)->join(', ').', ...';
                    })
                    ->badge()
                    ->wrap(),
                TextColumn::make('categories_count')->counts('categories')->label('Kategori')->badge(),
                TextColumn::make('questions_count')->counts('questions')->label('Pertanyaan')->badge(),
                TextColumn::make('responses_count')->counts('responses')->label('Hasil')->badge()->color('success'),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Aktif',
                        'closed' => 'Ditutup',
                    ]),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
