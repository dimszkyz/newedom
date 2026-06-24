<?php

namespace App\Filament\Resources\Prodis\Pages;

use App\Filament\Resources\Prodis\ProdiResource;
use App\Services\UnwProgramStudiSyncService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Throwable;

class ListProdis extends ListRecords
{
    protected static string $resource = ProdiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncProgramStudi')
                ->label('Sinkronisasi Prodi')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Sinkronisasi Prodi dari API UNW')
                ->modalDescription('Data prodi akan diambil dari API resmi UNW. Data yang sudah ada akan diperbarui berdasarkan ID prodi dari API.')
                ->modalSubmitActionLabel('Mulai Sinkronisasi')
                ->action(function (): void {
                    try {
                        $result = app(UnwProgramStudiSyncService::class)->sync();

                        Notification::make()
                            ->title('Sinkronisasi Prodi berhasil')
                            ->body("{$result['created']} data baru, {$result['updated']} data diperbarui, {$result['skipped']} data dilewati dari total {$result['total']} data API.")
                            ->success()
                            ->send();
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->title('Sinkronisasi Prodi gagal')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
