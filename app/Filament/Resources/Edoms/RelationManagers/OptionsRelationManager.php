<?php

namespace App\Filament\Resources\Edoms\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class OptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'options';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')
                    ->label('Opsi Jawaban')
                    ->required(),

                TextInput::make('score')
                    ->label('Nilai')
                    ->numeric()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),

                Tables\Columns\TextColumn::make('label')
                    ->label('Opsi Jawaban'),

                Tables\Columns\TextColumn::make('score')
                    ->label('Nilai'),
            ])

            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $lastSortOrder = $this->ownerRecord
                            ->options()
                            ->max('sort_order');

                        $data['sort_order'] = ($lastSortOrder ?? 0) + 1;

                        $data['edom_id'] = $this->ownerRecord->id;

                        return $data;
                    }),
            ])

            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
