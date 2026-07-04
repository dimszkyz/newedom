<?php

namespace App\Filament\Resources\EdomPeriods\Pages;

use App\Filament\Resources\EdomPeriods\EdomPeriodResource;
use App\Models\EdomPeriod;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageEdomPeriods extends ManageRecords
{
    protected static string $resource = EdomPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->after(function (EdomPeriod $record, array $data): void {
                    if (! isset($data['status'])) {
                        return;
                    }

                    $record->updateSettingsStatus((string) $data['status']);
                }),
        ];
    }
}
