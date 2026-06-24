<?php

namespace App\Filament\Resources\EdomQuestions\Pages;

use App\Filament\Resources\EdomQuestions\EdomQuestionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEdomQuestions extends ListRecords
{
    protected static string $resource = EdomQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
