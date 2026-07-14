<?php

namespace App\Filament\Resources\EdomResponses\Pages;

use App\Filament\Resources\EdomResponses\EdomResponseResource;
use App\Services\Edom\EdomResponsesExcelExporter;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListEdomResponses extends ListRecords
{
    protected static string $resource = EdomResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportAllExcel')
                ->label('Export Semua Response')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn (): StreamedResponse => $this->exportAllExcel()),
        ];
    }

    public function exportAllExcel(): StreamedResponse
    {
        $filename = 'edom-responses-semua-'.now()->format('Ymd-His').'.xls';

        return response()->streamDownload(
            function (): void {
                print app(EdomResponsesExcelExporter::class)->toXml();
            },
            $filename,
            [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            ],
        );
    }
}
