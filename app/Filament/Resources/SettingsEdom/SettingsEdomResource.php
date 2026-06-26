<?php

namespace App\Filament\Resources\SettingsEdom;

use App\Filament\Resources\SettingsEdom\Pages\CreateSettingsEdom;
use App\Filament\Resources\SettingsEdom\Pages\EditSettingsEdom;
use App\Filament\Resources\SettingsEdom\Pages\ListSettingsEdoms;
use App\Filament\Resources\SettingsEdom\RelationManagers\CategoriesRelationManager;
use App\Filament\Resources\SettingsEdom\RelationManagers\OptionsRelationManager;
use App\Filament\Resources\SettingsEdom\RelationManagers\ResponsesRelationManager;
use App\Filament\Resources\SettingsEdom\Schemas\SettingsEdomForm;
use App\Filament\Resources\SettingsEdom\Tables\SettingsEdomsTable;
use App\Models\SettingsEdom;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SettingsEdomResource extends Resource
{
    protected static ?string $model = SettingsEdom::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Setting EDOM';

    protected static ?string $modelLabel = 'EDOM';

    protected static ?string $pluralModelLabel = 'Setting EDOM';

    protected static ?string $slug = 'setting-edom';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return SettingsEdomForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SettingsEdomsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CategoriesRelationManager::class,
            OptionsRelationManager::class,
            ResponsesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSettingsEdoms::route('/'),
            'create' => CreateSettingsEdom::route('/create'),
            'edit' => EditSettingsEdom::route('/{record}/edit'),
        ];
    }
}
