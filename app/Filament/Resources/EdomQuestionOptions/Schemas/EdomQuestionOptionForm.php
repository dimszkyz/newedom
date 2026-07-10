<?php

namespace App\Filament\Resources\EdomQuestionOptions\Schemas;

use App\Models\EdomQuestionOption;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EdomQuestionOptionForm
{
    private const LOCK_HELPER = 'Opsi pertanyaan hanya dapat ditambah, diedit, atau dihapus saat EDOM Settings masih Draft dan belum memiliki response mahasiswa. Jika status Aktif/Ditutup atau sudah ada response, data ini dikunci.';

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama Opsi')
                ->required()
                ->maxLength(255)
                ->disabled(fn (?EdomQuestionOption $record): bool => $record?->edomSettings !== null && ! $record->edomSettings->canModifyQuestionMaster())
                ->helperText(self::LOCK_HELPER),
            TextInput::make('score')
                ->label('Nilai')
                ->numeric()
                ->required()
                ->disabled(fn (?EdomQuestionOption $record): bool => $record?->edomSettings !== null && ! $record->edomSettings->canModifyQuestionMaster())
                ->helperText(self::LOCK_HELPER),
        ]);
    }
}
