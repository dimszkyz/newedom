<?php

namespace App\Filament\Resources\EdomResponses\Tables;

use App\Models\EdomResponse;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EdomResponsesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->with(['edom', 'answers'])
                ->latest('submitted_at')
                ->latest('id'))
            ->columns([
                TextColumn::make('edom_name_snapshot')
                    ->label('EDOM')
                    ->state(fn (EdomResponse $record): string => $record->edom_name_snapshot ?: ($record->edom?->edom_name ?? 'EDOM dihapus'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('study_program_snapshot')
                    ->label('Prodi')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),

                TextColumn::make('course_snapshot')
                    ->label('Mata Kuliah')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),

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
            ->filters([
                SelectFilter::make('edom')
                    ->label('EDOM Aktif/Tersedia')
                    ->relationship('edom', 'edom_name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Semua EDOM'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Lihat Hasil'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
