<?php

namespace App\Filament\Resources\EdomQuestionOptions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EdomQuestionOptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('edom_setting_id')
                    ->label('Setting EDOM')
                    ->relationship('settingEdom', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('name')
                    ->label('Nama Opsi')
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
}
