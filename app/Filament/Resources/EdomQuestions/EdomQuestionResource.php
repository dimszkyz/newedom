<?php

namespace App\Filament\Resources\EdomQuestions;

use App\Filament\Resources\EdomQuestions\Pages\ListEdomQuestions;
use App\Filament\Resources\EdomQuestions\Schemas\EdomQuestionForm;
use App\Filament\Resources\EdomQuestions\Tables\EdomQuestionsTable;
use App\Models\EdomQuestion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EdomQuestionResource extends Resource
{
    protected static ?string $model = EdomQuestion::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'pernyataan';

    public static function form(Schema $schema): Schema
    {
        return EdomQuestionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EdomQuestionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEdomQuestions::route('/'),
        ];
    }
}