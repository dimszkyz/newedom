<?php

namespace App\Filament\Resources\EdomSettings\RelationManagers;

use App\Models\EdomQuestionOption;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class QuestionOptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questionOptions';

    protected static ?string $title = 'Opsi Pertanyaan';

    private const LOCK_HELPER = 'Opsi pertanyaan hanya dapat ditambah, diedit, atau dihapus saat EDOM Settings masih Draft. Jika status Aktif atau Ditutup, data ini dikunci.';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Opsi Jawaban')
                ->required()
                ->maxLength(255)
                ->disabled(fn (?EdomQuestionOption $record): bool => $record?->edomSettings !== null && ! $record->edomSettings->isDraft())
                ->helperText(self::LOCK_HELPER),

            TextInput::make('score')
                ->label('Nilai')
                ->numeric()
                ->required()
                ->disabled(fn (?EdomQuestionOption $record): bool => $record?->edomSettings !== null && ! $record->edomSettings->isDraft())
                ->helperText(self::LOCK_HELPER),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Opsi Jawaban'),
                Tables\Columns\TextColumn::make('score')->label('Nilai'),
                Tables\Columns\TextColumn::make('lock_info')
                    ->label('Keterangan')
                    ->state(fn (): string => $this->ownerRecord->isDraft()
                        ? 'Bisa diubah'
                        : 'Dikunci karena status EDOM Settings Aktif atau Ditutup')
                    ->badge()
                    ->color(fn (): string => $this->ownerRecord->isDraft() ? 'success' : 'warning'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $data['edom_setting_id'] = $this->ownerRecord->id;

                        return $data;
                    })
                    ->visible(fn (): bool => $this->ownerRecord->isDraft()),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (EdomQuestionOption $record): bool => $record->edomSettings?->isDraft() ?? false),
                DeleteAction::make()
                    ->visible(fn (EdomQuestionOption $record): bool => $record->edomSettings?->isDraft() ?? false),
            ]);
    }
}
