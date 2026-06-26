<?php

namespace App\Filament\Resources\SettingEdoms\Pages;

use App\Filament\Resources\SettingEdoms\SettingEdomResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSettingEdoms extends ListRecords
{
    protected static string $resource = SettingEdomResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
