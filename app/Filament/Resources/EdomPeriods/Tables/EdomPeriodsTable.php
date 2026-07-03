<?php

namespace App\Filament\Resources\EdomPeriods\Tables;

use App\Filament\Resources\EdomPeriods\Schemas\EdomPeriodForm;
use App\Models\EdomPeriod;
use App\Services\Siakad\UnwApiSiakad;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EdomPeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('year')->label('Tahun Ajaran')->sortable(),
                TextColumn::make('siakad_idsemester')
                    ->label('Semester')
                    ->formatStateUsing(function (mixed $state): string {
                        static $semesterOptions;

                        $semesterOptions ??= EdomPeriodForm::semesterOptions();

                        return $semesterOptions[(int) $state] ?? 'Semester '.$state;
                    })
                    ->sortable(),
                TextColumn::make('is_open_in_siakad')
                    ->label('Status SIAKAD')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Terbuka' : 'Ditutup')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
            ])
            ->recordActions([
                Action::make('openPeriod')
                    ->label('Buka ke SIAKAD')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (EdomPeriod $record): bool => $record->locksResponseUpdates())
                    ->action(function (EdomPeriod $record): void {
                        app(UnwApiSiakad::class)->openPeriod($record->year, $record->siakad_idsemester);
                        $record->markAsOpenInSiakad();

                        Notification::make()
                            ->title('Periode EDOM dibuka; jawaban dapat diperbarui kembali')
                            ->success()
                            ->send();
                    }),

                Action::make('closePeriod')
                    ->label('Tutup di SIAKAD')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (EdomPeriod $record): bool => $record->isOpenInSiakad())
                    ->action(function (EdomPeriod $record): void {
                        app(UnwApiSiakad::class)->closePeriod($record->year, $record->siakad_idsemester);
                        $record->markAsClosedInSiakad();

                        Notification::make()
                            ->title('Periode EDOM ditutup; jawaban lama tidak dapat diperbarui')
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
