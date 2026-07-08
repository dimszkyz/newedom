<?php

namespace App\Filament\Resources\EdomSettings\Schemas;

use App\Models\EdomSettings;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EdomSettingsForm
{
    private const LOCK_HELPER = 'Nama EdomSettings dan Program Studi hanya dapat diubah saat status masih Draft. Jika status Aktif atau Ditutup, bagian ini dikunci agar struktur EDOM yang sudah digunakan tidak berubah.';

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama EdomSettings')
                ->required()
                ->maxLength(255)
                ->disabled(fn (?EdomSettings $record): bool => $record !== null && ! $record->isDraft())
                ->helperText(self::LOCK_HELPER),

            Select::make('programStudis')
                ->label('Program Studi')
                ->relationship('programStudis', 'nama')
                ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                ->multiple()
                ->searchable()
                ->preload()
                ->required()
                ->disabled(fn (?EdomSettings $record): bool => $record !== null && ! $record->isDraft())
                ->helperText(self::LOCK_HELPER),

            Select::make('status')
                ->label('Status')
                ->options(EdomSettings::statusOptions())
                ->default(EdomSettings::STATUS_DRAFT)
                ->required()
                ->helperText('Saat status Aktif atau Ditutup, kategori, pertanyaan, dan opsi pertanyaan tidak dapat ditambah, diedit, atau dihapus.'),
        ]);
    }
}
