<?php

namespace App\Filament\Resources\EdomReports\Pages;

use App\Filament\Resources\EdomReports\EdomReportResource;
use App\Services\Edom\EdomKrsSectionSyncService;
use App\Services\Edom\EdomReportsExcelExporter;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListEdomReports extends ListRecords
{
    protected static string $resource = EdomReportResource::class;

    public function mount(): void
    {
        parent::mount();

        app(EdomKrsSectionSyncService::class)->syncKnownStudentPeriods();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportAllExcel')
                ->label('Export Semua Reports')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn (): StreamedResponse => $this->exportAllExcel()),
        ];
    }

    public function exportAllExcel(): StreamedResponse
    {
        $filename = 'edom-reports-semua-'.now()->format('Ymd-His').'.xls';

        return response()->streamDownload(
            function (): void {
                echo app(EdomReportsExcelExporter::class)->toXml();
            },
            $filename,
            [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            ],
        );
    }
}
