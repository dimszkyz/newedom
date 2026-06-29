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
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
            ])
            ->recordActions([
                Action::make('openPeriod')
                    ->label('Buka ke SIAKAD')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->requiresConfirmation(),

                Action::make('closePeriod')
                    ->label('Tutup di SIAKAD')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->requiresConfirmation(),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
