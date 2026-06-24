<?php

namespace App\Filament\Resources\EdomResponses;

use App\Filament\Resources\EdomResponses\Pages\ListEdomResponses;
use App\Filament\Resources\EdomResponses\Pages\ViewEdomResponse;
use App\Filament\Resources\EdomResponses\RelationManagers\AnswersRelationManager;
use App\Filament\Resources\EdomResponses\Schemas\EdomResponseInfolist;
use App\Filament\Resources\EdomResponses\Tables\EdomResponsesTable;
use App\Models\EdomResponse;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EdomResponseResource extends Resource
{
    protected static ?string $model = EdomResponse::class;

    protected static string|\UnitEnum|null $navigationGroup = 'EDOM';

    protected static ?string $navigationLabel = 'Hasil EDOM';

    protected static ?string $modelLabel = 'Hasil EDOM';

    protected static ?string $pluralModelLabel = 'Hasil EDOM';

    protected static ?string $slug = 'hasil-edom';

    protected static ?int $navigationSort = 20;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $recordTitleAttribute = 'nama_responden';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return EdomResponseInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EdomResponsesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AnswersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEdomResponses::route('/'),
            'view' => ViewEdomResponse::route('/{record}'),
        ];
    }
}
