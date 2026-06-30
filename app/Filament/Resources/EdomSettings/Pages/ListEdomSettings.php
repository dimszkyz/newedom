<?php

namespace App\Filament\Resources\EdomSettings\Pages;

use App\Filament\Resources\EdomSettings\SettingEdomResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEdomSettings extends ListRecords
{
    protected static string $resource = SettingEdomResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
