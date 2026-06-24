<?php

namespace App\Filament\Resources\EdomQuestions\Pages;

use App\Filament\Resources\EdomCategories\EdomCategoryResource;
use App\Filament\Resources\EdomQuestions\EdomQuestionResource;
use App\Filament\Resources\Edoms\EdomResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEdomQuestion extends EditRecord
{
    protected static string $resource = EdomQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function getBreadcrumbs(): array
    {
        $category = $this->record->category;
        $edom = $category?->edom;

        return [
            EdomResource::getUrl() => 'Kelola EDOM',
            $edom ? EdomResource::getUrl('edit', ['record' => $edom]) : '#' => $edom?->nama_edom ?? 'EDOM',
            $category ? EdomCategoryResource::getUrl('edit', ['record' => $category]) : '#' => $category?->nama_kategori ?? 'Kategori',
            '' => $this->record->pernyataan ?: 'Edit Pertanyaan',
        ];
    }
}
