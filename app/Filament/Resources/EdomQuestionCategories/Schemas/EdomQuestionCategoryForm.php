<?php

namespace App\Filament\Resources\EdomQuestionCategories\Schemas;

use App\Models\EdomQuestionCategory;
use App\Models\EdomSettings;
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
                ->options(fn (?EdomQuestionCategory $record): array => self::edomSettingOptions($record))
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

    private static function edomSettingOptions(?EdomQuestionCategory $record): array
    {
        return EdomSettings::query()
            ->where(function ($query) use ($record): void {
                $query->where('status', EdomSettings::STATUS_DRAFT);

                if ($record?->edom_setting_id !== null) {
                    $query->orWhereKey($record->edom_setting_id);
                }
            })
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
