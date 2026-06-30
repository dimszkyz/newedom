<?php

namespace App\Filament\Resources\EdomSettings;

use App\Filament\Resources\EdomSettings\Pages\CreateSettingEdom;
use App\Filament\Resources\EdomSettings\Pages\EditSettingEdom;
use App\Filament\Resources\EdomSettings\Pages\ListEdomSettings;
use App\Filament\Resources\EdomSettings\RelationManagers\CategoriesRelationManager;
use App\Filament\Resources\EdomSettings\RelationManagers\QuestionOptionsRelationManager;
use App\Filament\Resources\EdomSettings\RelationManagers\ResponsesRelationManager;
use App\Filament\Resources\EdomSettings\Schemas\SettingEdomForm;
use App\Filament\Resources\EdomSettings\Tables\EdomSettingsTable;
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
        return EdomSettingsTable::configure($table);
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
            'index' => ListEdomSettings::route('/'),
            'create' => CreateSettingEdom::route('/create'),
            'edit' => EditSettingEdom::route('/{record}/edit'),
        ];
    }
}
