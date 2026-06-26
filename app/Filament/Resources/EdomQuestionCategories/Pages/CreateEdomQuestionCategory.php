<?php

namespace App\Filament\Resources\EdomQuestionCategories\Pages;

use App\Filament\Resources\EdomQuestionCategories\EdomQuestionCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEdomQuestionCategory extends CreateRecord
{
    protected static string $resource = EdomQuestionCategoryResource::class;

    public function mount(): void
    {
        parent::mount();

        // Halaman create tidak dipakai langsung dari navigasi.
        // Kategori pertanyaan biasanya dibuat dari halaman Edit EDOM.
        if (! request()->filled('edom_setting_id') && ! request()->filled('edom_id')) {
            $this->redirect(EdomQuestionCategoryResource::getUrl('index'));
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $edomSettingId = request()->integer('edom_setting_id') ?: request()->integer('edom_id');

        if ($edomSettingId > 0) {
            $data['edom_setting_id'] = $edomSettingId;
        }

        return $data;
    }
}
