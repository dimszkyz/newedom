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
                TextInput::make('id_unw_program_studi')
                    ->label('ID API UNW')
                    ->disabled(),

                TextInput::make('nama')
                    ->label('Nama Prodi')
                    ->required(),
            ]);
    }
}
