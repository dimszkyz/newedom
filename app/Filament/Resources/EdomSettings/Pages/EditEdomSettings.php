<?php

namespace App\Filament\Resources\EdomSettings\Pages;

use App\Filament\Resources\EdomSettings\EdomSettingsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEdomSettings extends EditRecord
{
    protected static string $resource = EdomSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->visible(fn () => $this->record->isDraft())];
    }
}
