<?php

namespace App\Filament\Resources\ProgramStudis\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProgramStudiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Prodi')
                    ->required(),
            ]);
    }
}
