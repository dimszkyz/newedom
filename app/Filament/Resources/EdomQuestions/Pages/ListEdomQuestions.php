<?php

namespace App\Filament\Resources\EdomQuestions\Pages;

use App\Filament\Resources\EdomQuestions\EdomQuestionResource;
use App\Filament\Resources\EdomSettings\EdomSettingsResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListEdomQuestions extends ListRecords
{
    protected static string $resource = EdomQuestionResource::class;

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
