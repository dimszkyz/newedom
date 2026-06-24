<?php

namespace App\Filament\Resources\EdomQuestions\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class EdomQuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Hidden::make('category_id'),

                Textarea::make('statement')
                    ->label('Pernyataan')
                    ->required()
                    ->columnSpanFull(),

                Select::make('question_type')
                    ->label('Tipe Soal')
                    ->options([
                        'multiple_choice' => 'Pilihan Ganda',
                        'essay' => 'Esai',
                    ])
                    ->required(),
            ]);
    }
}
