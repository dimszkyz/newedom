<?php

namespace App\Filament\Resources\EdomPeriods\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EdomPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('year')->label('ID Tahun Ajaran SIAKAD')->numeric()->required(),
            TextInput::make('siakad_idsemester')->label('ID Semester SIAKAD')->numeric()->required(),
            TextInput::make('semester_name')->label('Nama Semester')->maxLength(100),
            Select::make('status')->label('Status')->options([
                'draft' => 'Draft',
                'open' => 'Dibuka',
                'closed' => 'Ditutup',
            ])->default('draft')->required(),
        ]);
    }
}
