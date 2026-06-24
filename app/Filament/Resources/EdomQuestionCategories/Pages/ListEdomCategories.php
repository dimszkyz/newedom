<?php

namespace App\Filament\Resources\EdomCategories\Pages;

use App\Filament\Resources\EdomCategories\EdomCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEdomCategories extends ListRecords
{
    protected static string $resource = EdomCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
