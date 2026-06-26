<?php

namespace App\Filament\Resources\EdomPeriods\Pages;

use App\Filament\Resources\EdomPeriods\EdomPeriodResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEdomPeriod extends EditRecord
{
    protected static string $resource = EdomPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
