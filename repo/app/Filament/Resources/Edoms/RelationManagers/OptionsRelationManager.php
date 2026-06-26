<?php

namespace App\Filament\Resources\Edoms\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class OptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'options';

    protected static ?string $title = 'Opsi Jawaban';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
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
                Tables\Columns\TextColumn::make('name')
                    ->label('Opsi Jawaban'),

                Tables\Columns\TextColumn::make('score')
                    ->label('Nilai')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $data['edom_setting_id'] = $this->ownerRecord->id;

                        return $data;
                    })
                    ->visible(fn ($livewire) => $livewire->ownerRecord->isDraft()),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn ($record) => $record->edom?->isDraft()),
                DeleteAction::make()
                    ->visible(fn ($record) => $record->edom?->isDraft()),
            ]);
    }
}
