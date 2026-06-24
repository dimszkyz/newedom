<?php

namespace App\Filament\Resources\EdomResponses\RelationManagers;

use App\Models\EdomAnswer;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AnswersRelationManager extends RelationManager
{
    protected static string $relationship = 'answers';

    protected static ?string $title = 'Detail Jawaban';

    protected static ?string $modelLabel = 'Jawaban';

    protected static ?string $pluralModelLabel = 'Detail Jawaban';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['question.category', 'option'])->orderBy('id'))
            ->columns([
                TextColumn::make('category_name_snapshot')
                    ->label('Kategori')
                    ->state(fn (EdomAnswer $record): string => $record->category_name_snapshot ?: ($record->question?->category?->category_name ?? 'Kategori dihapus'))
                    ->badge()
                    ->color('primary')
                    ->wrap(),

                TextColumn::make('statement_snapshot')
                    ->label('Pernyataan')
                    ->state(fn (EdomAnswer $record): string => $record->statement_snapshot ?: ($record->question?->statement ?? 'Pernyataan dihapus'))
                    ->limit(120)
                    ->wrap()
                    ->searchable(),

                TextColumn::make('option_label_snapshot')
                    ->label('Pilihan')
                    ->state(function (EdomAnswer $record): string {
                        $label = $record->option_label_snapshot ?: $record->option?->label;

                        if (filled($label)) {
                            return $label;
                        }

                        $score = $record->option_score_snapshot ?? $record->score;

                        if ($score !== null) {
                            return 'Opsi terhapus (nilai ' . $score . ')';
                        }

                        if (filled($record->answer_text)) {
                            return '-';
                        }

                        return '-';
                    })
                    ->badge()
                    ->color(fn (EdomAnswer $record): string => ($record->option_label_snapshot || $record->option?->label) ? 'success' : 'gray'),

                TextColumn::make('score')
                    ->label('Nilai')
                    ->state(fn (EdomAnswer $record): ?int => $record->option_score_snapshot ?? $record->score)
                    ->placeholder('-')
                    ->badge()
                    ->color('success'),

                TextColumn::make('answer_text')
                    ->label('Jawaban Teks')
                    ->placeholder('-')
                    ->limit(120)
                    ->wrap(),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
