<?php

namespace App\Filament\Resources\EdomSettings\Schemas;

use App\Models\EdomSettings;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;

class EdomSettingsForm
{
    private const LOCK_HELPER = 'Nama EdomSettings dan Program Studi hanya dapat diubah saat status masih Draft dan belum memiliki response mahasiswa. Jika status Aktif/Ditutup atau sudah ada response, bagian ini dikunci agar struktur EDOM yang sudah digunakan tidak berubah.';

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make([
                'default' => 1,
                'lg' => 2,
            ])
                ->schema([
                    Group::make()
                        ->schema([
                            TextInput::make('name')
                                ->label('Nama EdomSettings')
                                ->required()
                                ->maxLength(255)
                                ->disabled(fn (?EdomSettings $record): bool => $record !== null && ! $record->canModifyQuestionMaster())
                                ->helperText(self::LOCK_HELPER),

                            Select::make('status')
                                ->label('Status')
                                ->options(EdomSettings::statusOptions())
                                ->default(EdomSettings::STATUS_DRAFT)
                                ->required()
                                ->helperText('Saat status Aktif/Ditutup atau sudah ada response mahasiswa, kategori, pertanyaan, dan opsi pertanyaan tidak dapat ditambah, diedit, atau dihapus.'),
                        ]),

                    Group::make()
                        ->schema([
                            Select::make('programStudis')
                                ->label('Program Studi')
                                ->relationship('programStudis', 'nama')
                                ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->required()
                                ->disabled(fn (?EdomSettings $record): bool => $record !== null && ! $record->canModifyQuestionMaster()),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
