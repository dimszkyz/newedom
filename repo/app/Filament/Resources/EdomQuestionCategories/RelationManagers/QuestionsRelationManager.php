<?php

namespace App\Filament\Resources\EdomQuestionCategories\RelationManagers;

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

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('statement')->label('Pernyataan')->limit(80)->searchable(),
                Tables\Columns\TextColumn::make('question_type')->label('Tipe')->badge(),
                Tables\Columns\TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Pertanyaan')
                    ->slideOver()
                    ->schema([
                        Textarea::make('statement')->label('Pernyataan')->required()->columnSpanFull(),
                        Select::make('question_type')->label('Tipe Soal')->options([
                            'multiple_choice' => 'Pilihan Ganda',
                            'essay' => 'Esai',
                        ])->required(),
                    ])
                    ->using(function (array $data) {
                        $data['edom_question_category_id'] = $this->ownerRecord->id;
                        return $this->ownerRecord->questions()->create($data);
                    })
                    ->visible(fn ($livewire) => $livewire->ownerRecord->settingEdom->isDraft()),
            ])
            ->recordActions([
                EditAction::make()
                    ->slideOver()
                    ->schema([
                        Textarea::make('statement')->label('Pernyataan')->required()->columnSpanFull(),
                        Select::make('question_type')->label('Tipe Soal')->options([
                            'multiple_choice' => 'Pilihan Ganda',
                            'essay' => 'Esai',
                        ])->required(),
                    ])
                    ->visible(fn ($record) => $record->category?->settingEdom?->isDraft()),
                DeleteAction::make()->visible(fn ($record) => $record->category?->settingEdom?->isDraft()),
            ]);
    }
}
