<?php

namespace App\Filament\Resources\MataKuliahs;

use App\Filament\Resources\MataKuliahs\Pages\CreateMataKuliah;
use App\Filament\Resources\MataKuliahs\Pages\EditMataKuliah;
use App\Filament\Resources\MataKuliahs\Pages\ListMataKuliahs;
use App\Filament\Resources\MataKuliahs\Schemas\MataKuliahForm;
use App\Filament\Resources\MataKuliahs\Tables\MataKuliahsTable;
use App\Models\MataKuliah;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MataKuliahResource extends Resource
{
    protected static ?string $model = MataKuliah::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Mata Kuliah';

    protected static ?string $modelLabel = 'Mata Kuliah';
    
    protected static ?string $pluralModelLabel = 'Mata Kuliah';

    protected static ?string $slug = 'mata-kuliah';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return MataKuliahForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MataKuliahsTable::configure($table);
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
            'index' => ListMataKuliahs::route('/'),
            'create' => CreateMataKuliah::route('/create'),
            'edit' => EditMataKuliah::route('/{record}/edit'),
        ];
    }
}
