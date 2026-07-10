<?php

namespace App\Filament\Resources\EdomQuestionCategories\Pages;

use App\Filament\Resources\EdomQuestionCategories\EdomQuestionCategoryResource;
use App\Filament\Resources\EdomSettings\EdomSettingsResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEdomQuestionCategory extends EditRecord
{
    protected static string $resource = EdomQuestionCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn (): string => $this->record->edom_setting_id
                    ? EdomSettingsResource::getUrl('edit', ['record' => $this->record->edom_setting_id])
                    : EdomSettingsResource::getUrl('index')),
            DeleteAction::make()
                ->visible(fn (): bool => $this->record->edomSettings?->canModifyQuestionMaster() ?? false),
        ];
    }

    protected function getFormActions(): array
    {
        return ($this->record->edomSettings?->canModifyQuestionMaster() ?? false)
            ? parent::getFormActions()
            : [];
    }
}
