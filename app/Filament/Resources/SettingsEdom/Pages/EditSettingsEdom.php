<?php

namespace App\Filament\Resources\SettingsEdom\Pages;

use App\Filament\Resources\SettingsEdom\SettingsEdomResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSettingsEdom extends EditRecord
{
    protected static string $resource = SettingsEdomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}