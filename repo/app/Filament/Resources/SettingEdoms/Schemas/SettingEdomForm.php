<?php

namespace App\Filament\Resources\SettingEdoms\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SettingEdomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama Setting EDOM')
                ->required()
                ->maxLength(255)
                ->disabled(fn ($record) => $record && ! $record->isDraft()),

            Select::make('programStudis')
                ->label('Program Studi')
                ->relationship('programStudis', 'name')
                ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name ?? $record->name ?? '-')
                ->multiple()
                ->searchable()
                ->preload()
                ->required(),

            Select::make('status')
                ->label('Status')
                ->options([
                    'draft' => 'Draft',
                    'active' => 'Aktif',
                    'closed' => 'Ditutup',
                ])
                ->disabled(fn ($record) => $record && $record->status === 'closed')
                ->required(),
        ]);
    }
}
