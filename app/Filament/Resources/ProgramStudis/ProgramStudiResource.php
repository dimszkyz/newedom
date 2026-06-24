<?php

namespace App\Filament\Resources\ProgramStudis;

use App\Filament\Resources\ProgramStudis\Pages\ListProgramStudis;
use App\Filament\Resources\ProgramStudis\Schemas\ProgramStudiForm;
use App\Filament\Resources\ProgramStudis\Tables\ProgramStudisTable;
use App\Models\ProgramStudi;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProgramStudiResource extends Resource
{
    protected static ?string $model = ProgramStudi::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Prodi';

    protected static ?string $modelLabel = 'Prodi';

    protected static ?string $pluralModelLabel = 'Prodi';

    protected static ?string $slug = 'prodi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $recordTitleAttribute = 'name';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return ProgramStudiForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProgramStudisTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProgramStudis::route('/'),
        ];
    }
}
