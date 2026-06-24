<?php

namespace App\Filament\Resources\Edoms\Pages;

use App\Filament\Resources\Edoms\EdomResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEdom extends EditRecord
{
    protected static string $resource = EdomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}