<?php

namespace App\Filament\Resources\EdomSettings\Schemas;

use App\Models\EdomSettings;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EdomSettingsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama EdomSettings')
                ->required()
                ->maxLength(255),

            Select::make('programStudis')
                ->label('Program Studi')
                ->relationship('programStudis', 'nama')
                ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                ->multiple()
                ->searchable()
                ->preload()
                ->required(),

            Select::make('periods')
                ->label('Periode EDOM')
                ->relationship('periods', 'year')
                ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                ->multiple()
                ->searchable()
                ->preload()
                ->required()
                ->disabled(fn (?EdomSettings $record): bool => $record?->responses()->exists() ?? false)
                ->helperText('Setting hanya ditampilkan pada periode yang dipilih. Relasi periode dikunci setelah respons pertama tersimpan.'),

            Select::make('status')
                ->label('Status')
                ->options(EdomSettings::statusOptions())
                ->default(EdomSettings::STATUS_DRAFT)
                ->required(),
        ]);
    }
}
