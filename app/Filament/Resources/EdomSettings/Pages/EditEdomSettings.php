<?php

namespace App\Filament\Resources\EdomSettings\Pages;

use App\Filament\Resources\EdomSettings\EdomSettingsResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEdomSettings extends EditRecord
{
    protected static string $resource = EdomSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(EdomSettingsResource::getUrl('index')),
            DeleteAction::make()->visible(fn () => $this->record->isDraft()),
        ];
    }
}
