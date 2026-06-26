<?php

namespace App\Filament\Resources\ProgramStudis\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProgramStudiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('id_unw_program_studi')
                ->label('ID Program Studi API UNW')
                ->disabled(),

            TextInput::make('nama')
                ->label('Nama Program Studi')
                ->disabled(),
        ]);
    }
}
