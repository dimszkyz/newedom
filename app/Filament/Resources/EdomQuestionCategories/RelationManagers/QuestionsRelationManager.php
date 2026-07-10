<?php

namespace App\Filament\Resources\EdomQuestionCategories\RelationManagers;

use App\Models\EdomQuestion;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $title = 'Pertanyaan';

    private const LOCK_HELPER = 'Pertanyaan hanya dapat ditambah, diedit, atau dihapus saat EDOM Settings masih Draft dan belum memiliki response mahasiswa. Jika status Aktif/Ditutup atau sudah ada response, data ini dikunci.';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ViewColumn::make('statement')
                    ->label('Pernyataan')
                    ->view('filament.tables.columns.expandable-question-text')
                    ->searchable(),
                Tables\Columns\TextColumn::make('question_type')->label('Tipe')->badge(),
                Tables\Columns\TextColumn::make('lock_info')
                    ->label('Keterangan')
                    ->state(fn (): string => $this->ownerRecord->edomSettings?->questionMasterLockLabel() ?? 'Terkunci')
                    ->badge()
                    ->color(fn (): string => $this->ownerRecord->edomSettings?->canModifyQuestionMaster() ? 'success' : 'warning'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Pertanyaan')
                    ->slideOver()
                    ->schema([
                        Textarea::make('statement')
                            ->label('Pernyataan')
                            ->required()
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
                            ->helperText(self::LOCK_HELPER),
                    ])
                    ->using(function (array $data) {
                        $data['edom_question_category_id'] = $this->ownerRecord->id;
                        $data['edom_setting_id'] = $this->ownerRecord->edom_setting_id;

                        return $this->ownerRecord->questions()->create($data);
                    })
                    ->visible(fn (): bool => $this->ownerRecord->edomSettings?->canModifyQuestionMaster() ?? false),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit')
                    ->slideOver()
                    ->schema([
                        Textarea::make('statement')
                            ->label('Pernyataan')
                            ->required()
                            ->helperText(self::LOCK_HELPER)
                            ->columnSpanFull(),
                        Select::make('question_type')
                            ->label('Tipe Soal')
                            ->options([
                                'option' => 'Pilihan / Opsi',
                                'text' => 'Teks / Esai',
                            ])
                            ->required()
                            ->helperText(self::LOCK_HELPER),
                    ])
                    ->visible(fn (EdomQuestion $record): bool => $record->edomSettings?->canModifyQuestionMaster() ?? false),
                DeleteAction::make()
                    ->visible(fn (EdomQuestion $record): bool => $record->edomSettings?->canModifyQuestionMaster() ?? false),
            ]);
    }
}
