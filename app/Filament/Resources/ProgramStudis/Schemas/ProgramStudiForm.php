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
                ->label('id_unw_program_studi')
                ->disabled(),

            TextInput::make('display_name')
                ->label('Tampilan Admin')
                ->disabled(),

            TextInput::make('nama')
                ->label('Nama Program Studi')
                ->disabled(),

            TextInput::make('jenjang')
                ->label('Jenjang')
                ->disabled(),

            TextInput::make('jenjang_nama_singkat')
                ->label('Jenjang Singkat')
                ->disabled(),

            TextInput::make('slug')
                ->label('Slug')
                ->disabled(),

            TextInput::make('page_slug')
                ->label('Page Slug')
                ->disabled(),

            TextInput::make('id_unw_fakultas')
                ->label('ID Fakultas UNW')
                ->disabled(),

            TextInput::make('nama_fakultas')
                ->label('Nama Fakultas')
                ->disabled(),

            TextInput::make('page_slug_fakultas')
                ->label('Page Slug Fakultas')
                ->disabled(),
        ]);
    }
}
