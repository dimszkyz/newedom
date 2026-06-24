<?php

namespace App\Filament\Resources\EdomCategories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EdomCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('edom_setting_id')
                    ->label('EDOM')
                    ->relationship('edom', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('name')
                    ->label('Nama Kategori')
                    ->required()
                    ->maxLength(255),
            ]);
    }
}
