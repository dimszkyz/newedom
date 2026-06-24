<?php

namespace App\Filament\Resources\EdomQuestions\Pages;

use App\Filament\Resources\EdomQuestionCategories\EdomQuestionCategoryResource;
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
            $edom ? EdomResource::getUrl('edit', ['record' => $edom]) : '#' => $edom?->name ?? 'EDOM',
            $category ? EdomQuestionCategoryResource::getUrl('edit', ['record' => $category]) : '#' => $category?->name ?? 'Kategori',
            '' => $this->record->statement ?: 'Edit Pertanyaan',
        ];
    }
}
