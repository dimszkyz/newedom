<?php

namespace App\Filament\Resources\SettingEdoms;

use App\Filament\Resources\SettingEdoms\Pages\CreateSettingEdom;
use App\Filament\Resources\SettingEdoms\Pages\EditSettingEdom;
use App\Filament\Resources\SettingEdoms\Pages\ListSettingEdoms;
use App\Filament\Resources\SettingEdoms\RelationManagers\CategoriesRelationManager;
use App\Filament\Resources\SettingEdoms\RelationManagers\QuestionOptionsRelationManager;
use App\Filament\Resources\SettingEdoms\RelationManagers\ResponsesRelationManager;
use App\Filament\Resources\SettingEdoms\Schemas\SettingEdomForm;
use App\Filament\Resources\SettingEdoms\Tables\SettingEdomsTable;
use App\Models\SettingEdom;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SettingEdomResource extends Resource
{
    protected static ?string $model = SettingEdom::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'EDOM Setting';

    protected static ?string $modelLabel = 'EDOM Setting';

    protected static ?string $pluralModelLabel = 'EDOM Setting';

    protected static ?string $slug = 'edom_setting';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return SettingEdomForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SettingEdomsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CategoriesRelationManager::class,
            QuestionOptionsRelationManager::class,
            ResponsesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSettingEdoms::route('/'),
            'create' => CreateSettingEdom::route('/create'),
            'edit' => EditSettingEdom::route('/{record}/edit'),
        ];
    }
}
