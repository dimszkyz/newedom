<?php

namespace App\Filament\Resources\EdomQuestions\Pages;

use App\Filament\Resources\EdomQuestions\EdomQuestionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEdomQuestion extends CreateRecord
{
    protected static string $resource = EdomQuestionResource::class;

    public function mount(): void
    {
        parent::mount();

        if (! request()->filled('category_id')) {
            $this->redirect(EdomQuestionResource::getUrl('index'));
        }
    }
}