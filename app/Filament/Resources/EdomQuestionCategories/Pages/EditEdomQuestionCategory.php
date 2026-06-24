<?php

namespace App\Filament\Resources\EdomQuestionCategories\Pages;

use App\Filament\Resources\EdomQuestionCategories\EdomQuestionCategoryResource;
use App\Filament\Resources\SettingsEdom\SettingsEdomResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEdomQuestionCategory extends EditRecord
{
    protected static string $resource = EdomQuestionCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function getBreadcrumbs(): array
    {
        $edom = $this->record->edom;

        return [
            SettingsEdomResource::getUrl() => 'Kelola EDOM',
            SettingsEdomResource::getUrl('edit', [
                'record' => $edom,
            ]) => $edom->name,
            '' => $this->record->name,
        ];
    }
}