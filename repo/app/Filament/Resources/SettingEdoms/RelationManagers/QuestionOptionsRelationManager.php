<?php

namespace App\Filament\Resources\SettingEdoms\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class QuestionOptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questionOptions';

    protected static ?string $title = 'Opsi Jawaban';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Opsi Jawaban')
                    ->required()
                    ->maxLength(255),

                TextInput::make('score')
                    ->label('Nilai')
                    ->numeric()
                    ->required(),

                TextInput::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->default(1)
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

                Tables\Columns\TextColumn::make('name')
                    ->label('Opsi Jawaban'),

                Tables\Columns\TextColumn::make('score')
                    ->label('Nilai'),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $lastSortOrder = $this->ownerRecord
                            ->questionOptions()
                            ->max('sort_order');

                        $data['sort_order'] = $data['sort_order'] ?? (($lastSortOrder ?? 0) + 1);
                        $data['edom_setting_id'] = $this->ownerRecord->id;

                        return $data;
                    })
                    ->visible(fn ($livewire) => $livewire->ownerRecord->isDraft()),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn ($record) => $record->settingEdom?->isDraft()),

                DeleteAction::make()
                    ->visible(fn ($record) => $record->settingEdom?->isDraft()),
            ]);
    }
}
