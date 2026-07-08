<?php

namespace App\Filament\Resources\EdomQuestionCategories\Pages;

use App\Filament\Resources\EdomQuestionCategories\EdomQuestionCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEdomQuestionCategory extends EditRecord
{
    protected static string $resource = EdomQuestionCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => $this->record->edomSettings?->isDraft() ?? false),
        ];
    }

    protected function getFormActions(): array
    {
        return ($this->record->edomSettings?->isDraft() ?? false)
            ? parent::getFormActions()
            : [];
    }
}
