<?php

namespace App\Filament\Resources\EdomSettings\RelationManagers;

use App\Filament\Resources\EdomResponses\EdomResponseResource;
use App\Models\EdomResponse;
use App\Services\Edom\EdomResponseMetadata;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ResponsesRelationManager extends RelationManager
{
    protected static string $relationship = 'responses';

    protected static ?string $title = 'Hasil Pengisian';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->with(['period', 'details.questionOption'])
                ->withCount('details')
                ->latest('submitted_at')
                ->latest('id'))
            ->columns([
                TextColumn::make('student_name')
                    ->label('Nama Mahasiswa')
                    ->state(fn (EdomResponse $record): string => app(EdomResponseMetadata::class)->studentNameFor($record))
                    ->description(fn (EdomResponse $record): string => 'NIM: '.app(EdomResponseMetadata::class)->studentNimFor($record))
                    ->wrap(),
                TextColumn::make('course_label')
                    ->label('Mata Kuliah')
                    ->state(fn (EdomResponse $record): string => app(EdomResponseMetadata::class)->courseLabelFor($record))
                    ->wrap(),
                TextColumn::make('semester_label')
                    ->label('Semester')
                    ->state(fn (EdomResponse $record): string => app(EdomResponseMetadata::class)->semesterNameFor($record))
                    ->description(fn (EdomResponse $record): string => 'Tahun ajaran: '.app(EdomResponseMetadata::class)->tahunAjaranFor($record))
                    ->badge(),
                TextColumn::make('details_count')
                    ->counts('details')
                    ->label('Jawaban')
                    ->badge(),
                TextColumn::make('average_score')
                    ->label('Rata-rata Nilai')
                    ->state(fn (EdomResponse $record): string => app(EdomResponseMetadata::class)->formattedAverageScoreFor($record))
                    ->badge()
                    ->color('success'),
                TextColumn::make('submitted_at')
                    ->label('Waktu Submit')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Lihat Detail')
                    ->url(fn (EdomResponse $record): string => EdomResponseResource::getUrl('view', ['record' => $record])),
            ])
            ->toolbarActions([]);
    }
}
