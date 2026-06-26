<?php

namespace App\Filament\Resources\EdomPeriods;

use App\Filament\Resources\EdomPeriods\Pages\CreateEdomPeriod;
use App\Filament\Resources\EdomPeriods\Pages\EditEdomPeriod;
use App\Filament\Resources\EdomPeriods\Pages\ListEdomPeriods;
use App\Filament\Resources\EdomPeriods\Schemas\EdomPeriodForm;
use App\Filament\Resources\EdomPeriods\Tables\EdomPeriodsTable;
use App\Models\EdomPeriod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EdomPeriodResource extends Resource
{
    protected static ?string $model = EdomPeriod::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Periode EDOM';
    protected static ?string $modelLabel = 'Periode EDOM';
    protected static ?string $pluralModelLabel = 'Periode EDOM';
    protected static ?string $slug = 'periode-edom';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function form(Schema $schema): Schema { return EdomPeriodForm::configure($schema); }
    public static function table(Table $table): Table { return EdomPeriodsTable::configure($table); }

    public static function getPages(): array
    {
        return [
            'index' => ListEdomPeriods::route('/'),
            'create' => CreateEdomPeriod::route('/create'),
            'edit' => EditEdomPeriod::route('/{record}/edit'),
        ];
    }
}
