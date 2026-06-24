<?php

namespace App\Filament\Resources\MataKuliahs\Schemas;

use App\Models\Prodi;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MataKuliahForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('study_program_id')
                    ->label('Prodi')
                    ->relationship('prodi', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Prodi $record): string => $record->display_name)
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('name')
                    ->label('Nama Mata Kuliah')
                    ->required()
                    ->maxLength(255),
            ]);
    }
}
