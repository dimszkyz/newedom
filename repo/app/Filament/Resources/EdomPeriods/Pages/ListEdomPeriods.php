<?php

namespace App\Filament\Resources\EdomPeriods\Pages;

use App\Filament\Resources\EdomPeriods\EdomPeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEdomPeriods extends ListRecords
{
    protected static string $resource = EdomPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
