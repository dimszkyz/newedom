<?php

namespace App\Filament\Resources\EdomQuestionOptions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EdomQuestionOptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama Opsi')->required()->maxLength(255),
            TextInput::make('score')->label('Nilai')->numeric()->required(),
        ]);
    }
}
