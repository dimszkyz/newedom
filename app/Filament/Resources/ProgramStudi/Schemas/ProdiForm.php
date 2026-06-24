<?php

namespace App\Filament\Resources\Prodis\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProdiForm
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
