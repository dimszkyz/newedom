<?php

namespace App\Filament\Resources\EdomPeriods\Tables;

use App\Models\EdomPeriod;
use App\Services\Siakad\UnwApiSiakad;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class EdomPeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('year', 'desc')
            ->columns([
                TextColumn::make('year')
                    ->label('Tahun Ajaran')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('siakad_idsemester')
                    ->label('ID Semester')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('responses_count')
                    ->counts('responses')
                    ->label('Hasil')
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i'),
            ])
            ->recordActions([
                Action::make('openPeriod')
                    ->label('Buka di SIAKAD')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (EdomPeriod $record): void {
                        try {
                            app(UnwApiSiakad::class)->openPeriod($record->year, $record->siakad_idsemester);

                            Notification::make()
                                ->title('Periode EDOM dibuka di SIAKAD')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            Notification::make()
                                ->title('Gagal membuka periode')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('closePeriod')
                    ->label('Tutup di SIAKAD')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (EdomPeriod $record): void {
                        try {
                            app(UnwApiSiakad::class)->closePeriod($record->year, $record->siakad_idsemester);

                            Notification::make()
                                ->title('Periode EDOM ditutup di SIAKAD')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            Notification::make()
                                ->title('Gagal menutup periode')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
