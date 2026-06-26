<?php

namespace App\Filament\Resources\EdomQuestionOptions\Pages;

use App\Filament\Resources\EdomQuestionOptions\EdomQuestionOptionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEdomQuestionOptions extends ListRecords
{
    protected static string $resource = EdomQuestionOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
