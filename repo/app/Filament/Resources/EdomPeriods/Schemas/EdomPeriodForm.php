<?php

namespace App\Filament\Resources\EdomPeriods\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EdomPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('year')
                    ->label('Tahun Ajaran / ID Tahun Ajaran SIAKAD')
                    ->numeric()
                    ->default((int) date('Y'))
                    ->required(),

                Select::make('siakad_idsemester')
                    ->label('ID Semester SIAKAD')
                    ->options([
                        1 => '1',
                        2 => '2',
                        3 => '3',
                    ])
                    ->searchable()
                    ->required(),
            ]);
    }
}
