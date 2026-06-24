<?php

namespace App\Filament\Resources\EdomOptions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EdomOptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Opsi')
                    ->required()
                    ->maxLength(255),

                TextInput::make('score')
                    ->label('Nilai')
                    ->numeric()
                    ->required(),
            ]);
    }
}
