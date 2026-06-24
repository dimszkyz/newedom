<?php

namespace App\Filament\Resources\EdomOptions\Pages;

use App\Filament\Resources\EdomOptions\EdomOptionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEdomOptions extends ListRecords
{
    protected static string $resource = EdomOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
