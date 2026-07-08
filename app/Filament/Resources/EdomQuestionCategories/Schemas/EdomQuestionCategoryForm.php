<?php

namespace App\Filament\Resources\EdomQuestionCategories\Schemas;

use App\Models\EdomQuestionCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EdomQuestionCategoryForm
{
    private const LOCK_HELPER = 'Kategori hanya dapat ditambah, diedit, atau dihapus saat EDOM Settings masih Draft. Jika status Aktif atau Ditutup, data ini dikunci.';

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('edom_setting_id')
                ->label('EdomSettings')
                ->relationship('edomSettings', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->disabled(fn (?EdomQuestionCategory $record): bool => $record?->edomSettings !== null && ! $record->edomSettings->isDraft())
                ->helperText(self::LOCK_HELPER),
            TextInput::make('name')
                ->label('Nama Kategori')
                ->required()
                ->maxLength(255)
                ->disabled(fn (?EdomQuestionCategory $record): bool => $record?->edomSettings !== null && ! $record->edomSettings->isDraft())
                ->helperText(self::LOCK_HELPER),
        ]);
    }
}
