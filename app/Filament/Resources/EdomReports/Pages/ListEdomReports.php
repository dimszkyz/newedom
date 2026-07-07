<?php

namespace App\Filament\Resources\EdomReports\Pages;

use App\Filament\Resources\EdomReports\EdomReportResource;
use App\Services\Edom\EdomKrsReportData;
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

        app(EdomKrsReportData::class)->refreshKnownResponseMetadata();
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
        app(EdomKrsReportData::class)->refreshKnownResponseMetadata();

        $filename = 'edom-reports-semua-'.now()->format('Ymd-His').'.xls';

        return response()->streamDownload(
            function (): void {
                print app(EdomReportsExcelExporter::class)->toXml();
            },
            $filename,
            [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            ],
        );
    }
}
