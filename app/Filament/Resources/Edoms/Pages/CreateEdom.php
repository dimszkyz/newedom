<?php

namespace App\Filament\Resources\Edoms\Pages;

use App\Filament\Resources\Edoms\EdomResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEdom extends CreateRecord
{
    protected static string $resource = EdomResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCancelFormAction(), 
        ];
    }
}
