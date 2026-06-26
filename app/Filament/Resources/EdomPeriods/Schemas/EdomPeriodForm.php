<?php

namespace App\Filament\Resources\EdomPeriods\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EdomPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('year')
                ->label('ID Tahun Ajaran SIAKAD')
                ->numeric()
                ->required(),

            TextInput::make('siakad_idsemester')
                ->label('ID Semester SIAKAD')
                ->numeric()
                ->required(),
        ]);
    }
}
