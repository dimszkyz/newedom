<?php

namespace App\Filament\Resources\EdomQuestionOptions;

use App\Filament\Resources\EdomQuestionOptions\Pages\ListEdomQuestionOptions;
use App\Filament\Resources\EdomQuestionOptions\Schemas\EdomQuestionOptionForm;
use App\Filament\Resources\EdomQuestionOptions\Tables\EdomQuestionOptionsTable;
use App\Models\EdomQuestionOption;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EdomQuestionOptionResource extends Resource
{
    protected static ?string $model = EdomQuestionOption::class;
    protected static bool $shouldRegisterNavigation = false;
    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema { return EdomQuestionOptionForm::configure($schema); }
    public static function table(Table $table): Table { return EdomQuestionOptionsTable::configure($table); }
    public static function getPages(): array { return ['index' => ListEdomQuestionOptions::route('/')]; }
}
