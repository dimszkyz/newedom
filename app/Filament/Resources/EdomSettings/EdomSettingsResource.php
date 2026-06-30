<?php

namespace App\Filament\Resources\EdomSettings;

use App\Filament\Resources\EdomSettings\Pages\CreateEdomSettings;
use App\Filament\Resources\EdomSettings\Pages\EditEdomSettings;
use App\Filament\Resources\EdomSettings\Pages\ListEdomSettings;
use App\Filament\Resources\EdomSettings\RelationManagers\CategoriesRelationManager;
use App\Filament\Resources\EdomSettings\RelationManagers\QuestionOptionsRelationManager;
use App\Filament\Resources\EdomSettings\RelationManagers\ResponsesRelationManager;
use App\Filament\Resources\EdomSettings\Schemas\EdomSettingsForm;
use App\Filament\Resources\EdomSettings\Tables\EdomSettingsTable;
use App\Models\EdomSettings;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EdomSettingsResource extends Resource
{
    protected static ?string $model = EdomSettings::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'EDOM Settings';

    protected static ?string $modelLabel = 'EDOM Settings';

    protected static ?string $pluralModelLabel = 'EDOM Settings';

    protected static ?string $slug = 'edom-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return EdomSettingsForm::configure($schema);
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
            'create' => CreateEdomSettings::route('/create'),
            'edit' => EditEdomSettings::route('/{record}/edit'),
        ];
    }
}
