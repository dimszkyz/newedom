<?php

namespace App\Filament\Resources\EdomOptions;

use App\Filament\Resources\EdomOptions\Pages\ListEdomOptions;
use App\Filament\Resources\EdomOptions\Schemas\EdomOptionForm;
use App\Filament\Resources\EdomOptions\Tables\EdomOptionsTable;
use App\Models\EdomOption;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EdomOptionResource extends Resource
{
    protected static ?string $model = EdomOption::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'label';

    public static function form(Schema $schema): Schema
    {
        return EdomOptionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EdomOptionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEdomOptions::route('/'),
        ];
    }
}