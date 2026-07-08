<?php

namespace App\Filament\Resources\EdomSettings\Pages;

use App\Filament\Resources\EdomSettings\EdomSettingsResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateEdomSettings extends CreateRecord
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
        ];
    }
}
