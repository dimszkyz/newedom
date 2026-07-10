<?php

namespace App\Filament\Resources\EdomQuestions\Schemas;

use App\Models\EdomQuestion;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class EdomQuestionForm
{
    private const LOCK_HELPER = 'Pertanyaan hanya dapat ditambah, diedit, atau dihapus saat EDOM Settings masih Draft dan belum memiliki response mahasiswa. Jika status Aktif/Ditutup atau sudah ada response, data ini dikunci.';

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('edom_question_category_id'),
            Hidden::make('edom_setting_id'),

            Textarea::make('statement')
                ->label('Pernyataan')
                ->required()
                ->disabled(fn (?EdomQuestion $record): bool => $record?->edomSettings !== null && ! $record->edomSettings->canModifyQuestionMaster())
                ->helperText(self::LOCK_HELPER)
                ->columnSpanFull(),

            Select::make('question_type')
                ->label('Tipe Soal')
                ->options([
                    'option' => 'Pilihan / Opsi',
                    'text' => 'Teks / Esai',
                ])
                ->default('option')
                ->required()
                ->disabled(fn (?EdomQuestion $record): bool => $record?->edomSettings !== null && ! $record->edomSettings->canModifyQuestionMaster())
                ->helperText(self::LOCK_HELPER),
        ]);
    }
}
