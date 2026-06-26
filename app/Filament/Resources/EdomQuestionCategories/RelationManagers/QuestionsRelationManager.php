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
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Pertanyaan')
                    ->slideOver()
                    ->schema([
                        Textarea::make('statement')->label('Pernyataan')->required()->columnSpanFull(),
                        Select::make('question_type')
                            ->label('Tipe Soal')
                            ->options([
                                'option' => 'Pilihan / Opsi',
                                'text' => 'Teks / Esai',
                            ])
                            ->default('option')
                            ->required(),
                    ])
                    ->using(function (array $data) {
                        $data['edom_question_category_id'] = $this->ownerRecord->id;
                        $data['edom_setting_id'] = $this->ownerRecord->edom_setting_id;

                        return $this->ownerRecord->questions()->create($data);
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit')
                    ->slideOver()
                    ->schema([
                        Textarea::make('statement')->label('Pernyataan')->required()->columnSpanFull(),
                        Select::make('question_type')
                            ->label('Tipe Soal')
                            ->options([
                                'option' => 'Pilihan / Opsi',
                                'text' => 'Teks / Esai',
                            ])
                            ->required(),
                    ]),
                DeleteAction::make(),
            ]);
    }
}
