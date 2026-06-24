<?php

namespace App\Filament\Resources\Prodis;

use App\Filament\Resources\Prodis\Pages\ListProdis;
use App\Filament\Resources\Prodis\Schemas\ProdiForm;
use App\Filament\Resources\Prodis\Tables\ProdisTable;
use App\Models\Prodi;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProdiResource extends Resource
{
    protected static ?string $model = Prodi::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Prodi';

    protected static ?string $modelLabel = 'Prodi';

    protected static ?string $pluralModelLabel = 'Prodi';

    protected static ?string $slug = 'prodi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $recordTitleAttribute = 'nama';

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
        return ProdiForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProdisTable::configure($table);
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
            'index' => ListProdis::route('/'),
        ];
    }
}
