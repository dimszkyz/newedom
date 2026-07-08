<?php

namespace App\Filament\Resources\EdomQuestionOptions\Pages;

use App\Filament\Resources\EdomQuestionOptions\EdomQuestionOptionResource;
use App\Filament\Resources\EdomSettings\EdomSettingsResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListEdomQuestionOptions extends ListRecords
{
    protected static string $resource = EdomQuestionOptionResource::class;

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
