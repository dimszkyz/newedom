<?php

namespace App\Filament\Resources\EdomCategories;

use App\Filament\Resources\EdomCategories\Pages\CreateEdomCategory;
use App\Filament\Resources\EdomCategories\Pages\EditEdomCategory;
use App\Filament\Resources\EdomCategories\Pages\ListEdomCategories;
use App\Filament\Resources\EdomCategories\RelationManagers\QuestionsRelationManager;
use App\Filament\Resources\EdomCategories\Schemas\EdomCategoryForm;
use App\Filament\Resources\EdomCategories\Tables\EdomCategoriesTable;
use App\Models\EdomCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EdomCategoryResource extends Resource
{
    protected static ?string $model = EdomCategory::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'nama_kategori';

    public static function form(Schema $schema): Schema
    {
        return EdomCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EdomCategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            QuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEdomCategories::route('/'),
            'create' => CreateEdomCategory::route('/create'),
            'edit' => EditEdomCategory::route('/{record}/edit'),
        ];
    }
}