<?php

namespace App\Filament\Resources\EdomQuestionCategories\Pages;

use App\Filament\Resources\EdomQuestionCategories\EdomQuestionCategoryResource;
use App\Filament\Resources\EdomSettings\EdomSettingsResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateEdomQuestionCategory extends CreateRecord
{
    protected static string $resource = EdomQuestionCategoryResource::class;

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
