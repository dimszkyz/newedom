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
            ->columns([
                TextColumn::make('year')->label('Tahun Ajaran')->sortable(),
                TextColumn::make('siakad_idsemester')->label('ID Semester')->sortable(),
                TextColumn::make('semester_name')->label('Semester')->placeholder('-')->searchable(),
                TextColumn::make('status')->label('Status')->badge()->color(fn (string $state): string => match ($state) {
                    'open' => 'success',
                    'closed' => 'danger',
                    default => 'gray',
                }),
                TextColumn::make('opened_at')->label('Dibuka')->dateTime('d M Y H:i')->placeholder('-'),
                TextColumn::make('closed_at')->label('Ditutup')->dateTime('d M Y H:i')->placeholder('-'),
            ])
            ->recordActions([
                Action::make('openPeriod')
                    ->label('Buka')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (EdomPeriod $record): bool => ! $record->isOpen())
                    ->action(function (EdomPeriod $record): void {
                        try {
                            app(UnwApiSiakad::class)->openPeriod($record->year, $record->siakad_idsemester);
                            $record->update(['status' => 'open', 'opened_at' => now(), 'closed_at' => null]);
                            Notification::make()->title('Periode EDOM berhasil dibuka')->success()->send();
                        } catch (Throwable $exception) {
                            report($exception);
                            Notification::make()->title('Gagal membuka periode EDOM')->body($exception->getMessage())->danger()->send();
                        }
                    }),
                Action::make('closePeriod')
                    ->label('Tutup')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (EdomPeriod $record): bool => $record->isOpen())
                    ->action(function (EdomPeriod $record): void {
                        try {
                            app(UnwApiSiakad::class)->closePeriod($record->year, $record->siakad_idsemester);
                            $record->update(['status' => 'closed', 'closed_at' => now()]);
                            Notification::make()->title('Periode EDOM berhasil ditutup')->success()->send();
                        } catch (Throwable $exception) {
                            report($exception);
                            Notification::make()->title('Gagal menutup periode EDOM')->body($exception->getMessage())->danger()->send();
                        }
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
