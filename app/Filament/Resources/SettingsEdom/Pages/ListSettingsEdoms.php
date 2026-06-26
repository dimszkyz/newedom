<?php

namespace App\Filament\Resources\SettingsEdom\Pages;

use App\Filament\Resources\SettingsEdom\SettingsEdomResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSettingsEdoms extends ListRecords
{
    protected static string $resource = SettingsEdomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
