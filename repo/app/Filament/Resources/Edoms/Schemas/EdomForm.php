<?php

namespace App\Filament\Resources\Edoms\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EdomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama EDOM')
                    ->required()
                    ->maxLength(255)
                    ->disabled(fn ($record) => $record && ! $record->isDraft()),

                Select::make('prodis')
                    ->label('Prodi')
                    ->relationship('prodis', 'nama')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name ?? $record->nama ?? '-')
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
