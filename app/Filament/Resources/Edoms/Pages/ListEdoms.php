<?php

namespace App\Filament\Resources\Edoms\Pages;

use App\Filament\Resources\Edoms\EdomResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEdoms extends ListRecords
{
    protected static string $resource = EdomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
