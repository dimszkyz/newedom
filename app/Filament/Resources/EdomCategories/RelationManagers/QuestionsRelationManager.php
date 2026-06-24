<?php

namespace App\Filament\Resources\EdomCategories\RelationManagers;

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

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('statement')
                    ->label('Pernyataan')
                    ->limit(80)
                    ->searchable(),

                Tables\Columns\TextColumn::make('question_type')
                    ->label('Tipe')
                    ->badge(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('New Question')
                    ->slideOver()
                    ->schema([
                        Textarea::make('statement')
                            ->label('Pernyataan')
                            ->required()
                            ->columnSpanFull(),

                        Select::make('question_type')
                            ->label('Tipe Soal')
                            ->options([
                                'multiple_choice' => 'Pilihan Ganda',
                                'essay' => 'Esai',
                            ])
                            ->required(),
                    ])
                    ->using(function (array $data) {
                        $data['category_id'] = $this->ownerRecord->id;

                        return $this->ownerRecord->questions()->create($data);
                    })
                    ->visible(fn ($livewire) => $livewire->ownerRecord->edom->isDraft()),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit')
                    ->slideOver()
                    ->schema([
                        Textarea::make('statement')
                            ->label('Pernyataan')
                            ->required()
                            ->columnSpanFull(),

                        Select::make('question_type')
                            ->label('Tipe Soal')
                            ->options([
                                'multiple_choice' => 'Pilihan Ganda',
                                'essay' => 'Esai',
                            ])
                            ->required(),
                    ])
                    ->using(function ($record, array $data) {
                        $record->update($data);

                        return $record;
                    })
                    ->visible(fn ($record) => $record->category?->edom?->isDraft()),

                DeleteAction::make()
                    ->visible(fn ($record) => $record->category?->edom?->isDraft()),
            ]);
    }
}
