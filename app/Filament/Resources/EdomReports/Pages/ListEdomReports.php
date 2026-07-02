<?php

namespace App\Filament\Resources\EdomReports\Pages;

use App\Filament\Resources\EdomReports\EdomReportResource;
use App\Services\Edom\EdomKrsSectionSyncService;
use Filament\Resources\Pages\ListRecords;

class ListEdomReports extends ListRecords
{
    protected static string $resource = EdomReportResource::class;

    public function mount(): void
    {
        parent::mount();

        app(EdomKrsSectionSyncService::class)->syncKnownStudentPeriods();
    }
}
