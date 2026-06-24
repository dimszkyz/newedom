<?php

namespace App\Filament\Resources\EdomResponses\Pages;

use App\Filament\Resources\EdomResponses\EdomResponseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEdomResponse extends ViewRecord
{
    protected static string $resource = EdomResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
