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
            ->modifyQueryUsing(fn ($query) => $query->with(['settingEdom', 'details'])->latest('submitted_at')->latest('id'))
            ->columns([
                TextColumn::make('edom_name_snapshot')
                    ->label('Setting EDOM')
                    ->state(fn (EdomResponse $record): string => $record->edom_name_snapshot ?: ($record->settingEdom?->name ?? 'Setting EDOM dihapus'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('study_program_snapshot')->label('Program Studi')->placeholder('-')->toggleable(isToggledHiddenByDefault: true)->wrap(),
                TextColumn::make('course_snapshot')->label('Mata Kuliah dari KRS')->placeholder('-')->wrap(),
                TextColumn::make('lecturer_name_snapshot')->label('Dosen')->placeholder('-')->searchable(),
                TextColumn::make('respondent_name')->label('Nama Mahasiswa')->placeholder('Anonim')->searchable(),
                TextColumn::make('student_number')->label('NIM')->placeholder('-')->searchable(),
                TextColumn::make('details_count')->counts('details')->label('Jawaban')->badge(),
                TextColumn::make('average_score')
                    ->label('Rata-rata Nilai')
                    ->state(function (EdomResponse $record): string {
                        $average = $record->details->whereNotNull('score')->avg('score');
                        return $average === null ? '-' : number_format((float) $average, 2, ',', '.');
                    })
                    ->badge()
                    ->color('success'),
                TextColumn::make('submitted_at')->label('Dikirim')->dateTime('d M Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('settingEdom')
                    ->label('Setting EDOM')
                    ->relationship('settingEdom', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Semua Setting EDOM'),
            ])
            ->recordActions([ViewAction::make()->label('Lihat Hasil')])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
