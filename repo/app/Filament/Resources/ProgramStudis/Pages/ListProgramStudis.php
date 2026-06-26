<?php

namespace App\Filament\Resources\ProgramStudis\Pages;

use App\Filament\Resources\ProgramStudis\ProgramStudiResource;
use App\Services\UnwProgramStudiSyncService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Throwable;

class ListProgramStudis extends ListRecords
{
    protected static string $resource = ProgramStudiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncProgramStudi')
                ->label('Sinkronisasi Program Studi')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Sinkronisasi Program Studi dari API UNW')
                ->modalDescription('Data program studi akan diambil dari API resmi UNW. Data yang sudah ada akan diperbarui berdasarkan ID program studi dari API.')
                ->modalSubmitActionLabel('Mulai Sinkronisasi')
                ->action(function (): void {
                    try {
                        $result = app(UnwProgramStudiSyncService::class)->sync();

                        Notification::make()
                            ->title('Sinkronisasi Program Studi berhasil')
                            ->body("{$result['created']} data baru, {$result['updated']} data diperbarui, {$result['skipped']} data dilewati dari total {$result['total']} data API.")
                            ->success()
                            ->send();

                        $this->resetTable();
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title('Sinkronisasi Program Studi gagal')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
