<?php

namespace App\Filament\Resources\SettingsEdom\Pages;

use App\Filament\Resources\SettingsEdom\SettingsEdomResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSettingsEdom extends CreateRecord
{
    protected static string $resource = SettingsEdomResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCancelFormAction(), 
        ];
    }
}
