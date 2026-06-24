<?php

namespace App\Filament\Resources\EdomQuestionCategories\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';
}
