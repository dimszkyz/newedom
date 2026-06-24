<?php

namespace App\Filament\Resources\EdomCategories\Pages;

use App\Filament\Resources\EdomCategories\EdomCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEdomCategory extends CreateRecord
{
    protected static string $resource = EdomCategoryResource::class;

    public function mount(): void
    {
        parent::mount();

        // Halaman create tidak boleh diakses langsung.
        // Semua kategori harus dibuat dari halaman Edit EDOM.
        if (! request()->filled('edom_id')) {
            $this->redirect(EdomCategoryResource::getUrl('index'));
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['edom_id'] = request()->integer('edom_id');

        return $data;
    }
}