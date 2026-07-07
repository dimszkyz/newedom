<?php

namespace App\Filament\Resources\EdomPeriods\Tables;

use App\Filament\Resources\EdomPeriods\Schemas\EdomPeriodForm;
use App\Models\EdomPeriod;
use App\Models\EdomSettings;
use App\Services\Siakad\UnwApiSiakad;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EdomPeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('settings'))
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
                TextColumn::make('status')
                    ->label('Status EDOM')
                    ->formatStateUsing(fn (string $state): string => EdomSettings::statusOptions()[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        EdomSettings::STATUS_ACTIVE => 'success',
                        EdomSettings::STATUS_CLOSED => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('lifecycle_status')
                    ->label('Status SIAKAD')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Terbuka' => 'success',
                        'Pembaruan Dikunci' => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
            ])
            ->recordActions([
                Action::make('openPeriod')
                    ->label('Buka ke SIAKAD')
                    ->icon(Heroicon::OutlinedLockOpen)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (EdomPeriod $record): bool => ! $record->isOpenInSiakad())
                    ->action(function (EdomPeriod $record): void {
                        app(UnwApiSiakad::class)->openPeriod($record->year, $record->siakad_idsemester);
                        $record->markAsOpenInSiakad();

                        Notification::make()
                            ->title('Periode EDOM dibuka; jawaban dapat diperbarui kembali')
                            ->success()
                            ->send();
                    }),

                Action::make('lockUpdates')
                    ->label('Kunci Pembaruan')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (EdomPeriod $record): bool => $record->allowsResponseUpdates())
                    ->action(function (EdomPeriod $record): void {
                        $record->lockResponseUpdates();

                        Notification::make()
                            ->title('Pembaruan dikunci; pengisian baru tetap dibuka')
                            ->warning()
                            ->send();
                    }),

                Action::make('unlockUpdates')
                    ->label('Buka Pembaruan')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (EdomPeriod $record): bool => $record->isOpenInSiakad()
                        && $record->locksResponseUpdates())
                    ->action(function (EdomPeriod $record): void {
                        $record->unlockResponseUpdates();

                        Notification::make()
                            ->title('Jawaban mahasiswa dapat diperbarui kembali')
                            ->success()
                            ->send();
                    }),

                Action::make('closePeriod')
                    ->label('Tutup di SIAKAD')
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (EdomPeriod $record): bool => $record->isOpenInSiakad())
                    ->action(function (EdomPeriod $record): void {
                        app(UnwApiSiakad::class)->closePeriod($record->year, $record->siakad_idsemester);
                        $record->markAsClosedInSiakad();

                        Notification::make()
                            ->title('Ditutup di SIAKAD; pengisian baru tetap dibuka, pembaruan dikunci')
                            ->success()
                            ->send();
                    }),

                EditAction::make()
                    ->after(function (EdomPeriod $record, array $data): void {
                        if (! isset($data['status'])) {
                            return;
                        }

                        $record->updateSettingsStatus((string) $data['status']);
                    })
                    ->visible(fn (EdomPeriod $record): bool => ! $record->responses()->exists()),
            ])
            ->toolbarActions([]);
    }
}
