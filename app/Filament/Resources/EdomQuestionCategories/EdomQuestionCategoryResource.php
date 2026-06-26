<?php

namespace App\Filament\Resources\EdomQuestionCategories;

use App\Filament\Resources\EdomQuestionCategories\Pages\CreateEdomQuestionCategory;
use App\Filament\Resources\EdomQuestionCategories\Pages\EditEdomQuestionCategory;
use App\Filament\Resources\EdomQuestionCategories\Pages\ListEdomQuestionCategories;
use App\Filament\Resources\EdomQuestionCategories\RelationManagers\QuestionsRelationManager;
use App\Filament\Resources\EdomQuestionCategories\Schemas\EdomQuestionCategoryForm;
use App\Filament\Resources\EdomQuestionCategories\Tables\EdomQuestionCategoriesTable;
use App\Models\EdomQuestionCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EdomQuestionCategoryResource extends Resource
{
    protected static ?string $model = EdomQuestionCategory::class;
    protected static bool $shouldRegisterNavigation = false;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $modelLabel = 'Kategori Pertanyaan EDOM';
    protected static ?string $pluralModelLabel = 'Kategori Pertanyaan EDOM';

    public static function form(Schema $schema): Schema { return EdomQuestionCategoryForm::configure($schema); }
    public static function table(Table $table): Table { return EdomQuestionCategoriesTable::configure($table); }
    public static function getRelations(): array { return [QuestionsRelationManager::class]; }

    public static function getPages(): array
    {
        return [
            'index' => ListEdomQuestionCategories::route('/'),
            'create' => CreateEdomQuestionCategory::route('/create'),
            'edit' => EditEdomQuestionCategory::route('/{record}/edit'),
        ];
    }
}
