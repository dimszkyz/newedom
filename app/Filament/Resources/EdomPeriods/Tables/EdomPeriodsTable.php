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
use App\Filament\Resources\EdomPeriods\Schemas\EdomPeriodForm;

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

                        return $semesterOptions[(int) $state] ?? 'Semester ' . $state;
                    })
                    ->sortable(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
            ])
            ->recordActions([
                Action::make('openPeriod')
                    ->label('Buka ke SIAKAD')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (EdomPeriod $record): void {
                        app(UnwApiSiakad::class)->openPeriod($record->year, $record->siakad_idsemester);

                        Notification::make()
                            ->title('Periode EDOM berhasil dibuka di SIAKAD')
                            ->success()
                            ->send();
                    }),

                Action::make('closePeriod')
                    ->label('Tutup di SIAKAD')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (EdomPeriod $record): void {
                        app(UnwApiSiakad::class)->closePeriod($record->year, $record->siakad_idsemester);

                        Notification::make()
                            ->title('Periode EDOM berhasil ditutup di SIAKAD')
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
