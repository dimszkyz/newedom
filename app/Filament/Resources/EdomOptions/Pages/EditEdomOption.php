<?php

namespace App\Filament\Resources\EdomOptions\Pages;

use App\Filament\Resources\EdomOptions\EdomOptionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEdomOption extends EditRecord
{
    protected static string $resource = EdomOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}