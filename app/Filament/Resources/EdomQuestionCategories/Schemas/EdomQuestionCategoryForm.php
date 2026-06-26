<?php

namespace App\Filament\Resources\EdomQuestionCategories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EdomQuestionCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('edom_setting_id')
                ->label('Setting EDOM')
                ->relationship('settingEdom', 'name')
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
