<?php

namespace App\Filament\Resources\EdomCategories\Pages;

use App\Filament\Resources\EdomCategories\EdomCategoryResource;
use App\Filament\Resources\Edoms\EdomResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEdomCategory extends EditRecord
{
    protected static string $resource = EdomCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function getBreadcrumbs(): array
    {
        $edom = $this->record->edom;

        return [
            EdomResource::getUrl() => 'Kelola EDOM',
            EdomResource::getUrl('edit', [
                'record' => $edom,
            ]) => $edom->nama_edom,
            '' => $this->record->nama_kategori,
        ];
    }
}