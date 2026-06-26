<?php

namespace App\Filament\Resources\EdomQuestionOptions\Pages;

use App\Filament\Resources\EdomQuestionOptions\EdomQuestionOptionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEdomQuestionOption extends EditRecord
{
    protected static string $resource = EdomQuestionOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}