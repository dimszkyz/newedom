<?php

namespace App\Filament\Resources\SettingEdoms\Pages;

use App\Filament\Resources\SettingEdoms\SettingEdomResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSettingEdom extends EditRecord
{
    protected static string $resource = SettingEdomResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->visible(fn () => $this->record->isDraft())];
    }
}
