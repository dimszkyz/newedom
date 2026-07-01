<?php

namespace App\Filament\Resources\EdomResponses;

use App\Filament\Resources\EdomResponses\Pages\ListEdomResponses;
use App\Filament\Resources\EdomResponses\Pages\ViewEdomResponse;
use App\Filament\Resources\EdomResponses\RelationManagers\DetailsRelationManager;
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
    protected static ?string $navigationLabel = 'EDOM Responses';
    protected static ?string $modelLabel = 'EDOM Responses';
    protected static ?string $pluralModelLabel = 'EDOM Responses';
    protected static ?string $slug = 'edom-response';
    protected static ?int $navigationSort = 20;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;
    protected static ?string $recordTitleAttribute = 'siakad_idtawarmatakuliahdetail';

    public static function canCreate(): bool { return false; }
    public static function infolist(Schema $schema): Schema { return EdomResponseInfolist::configure($schema); }
    public static function table(Table $table): Table { return EdomResponsesTable::configure($table); }
    public static function getRelations(): array { return [DetailsRelationManager::class]; }

    public static function getPages(): array
    {
        return [
            'index' => ListEdomResponses::route('/'),
            'view' => ViewEdomResponse::route('/{record}'),
        ];
    }
}
