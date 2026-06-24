<?php

namespace App\Filament\Resources\Edoms\RelationManagers;

use App\Filament\Resources\EdomResponses\EdomResponseResource;
use App\Models\EdomResponse;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ResponsesRelationManager extends RelationManager
{
    protected static string $relationship = 'responses';

    protected static ?string $title = 'Hasil Pengisian';

    protected static ?string $modelLabel = 'Hasil Pengisian';

    protected static ?string $pluralModelLabel = 'Hasil Pengisian';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->with('answers')
                ->latest('submitted_at')
                ->latest('id'))
            ->columns([
                TextColumn::make('respondent_name')
                    ->label('Nama Mahasiswa')
                    ->placeholder('Anonim')
                    ->searchable(),

                TextColumn::make('student_number')
                    ->label('NIM')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('answers_count')
                    ->counts('answers')
                    ->label('Jawaban')
                    ->badge(),

                TextColumn::make('average_score')
                    ->label('Rata-rata Nilai')
                    ->state(function (EdomResponse $record): string {
                        $average = $record->answers
                            ->whereNotNull('score')
                            ->avg('score');

                        return $average === null ? '-' : number_format((float) $average, 2, ',', '.');
                    })
                    ->badge()
                    ->color('success'),

                TextColumn::make('submitted_at')
                    ->label('Dikirim')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Lihat Hasil')
                    ->url(fn (EdomResponse $record): string => EdomResponseResource::getUrl('view', ['record' => $record])),
            ])
            ->toolbarActions([]);
    }
}
