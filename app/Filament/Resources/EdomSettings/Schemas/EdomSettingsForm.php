<?php

namespace App\Filament\Resources\EdomSettings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EdomSettingsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama EdomSettings')
                ->required()
                ->maxLength(255),

            Select::make('programStudis')
                ->label('Program Studi')
                ->relationship('programStudis', 'nama')
                ->multiple()
                ->searchable()
                ->preload()
                ->required(),

            Select::make('status')
                ->label('Status')
                ->options([
                    'draft' => 'Draft',
                    'active' => 'Aktif',
                    'closed' => 'Ditutup',
                ])
                ->default('draft')
                ->required(),
        ]);
    }
}
