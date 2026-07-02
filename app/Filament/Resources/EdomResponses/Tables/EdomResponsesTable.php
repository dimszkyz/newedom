<?php

namespace App\Filament\Resources\EdomResponses\Tables;

use App\Filament\Resources\EdomResponses\EdomResponseResource;
use App\Models\EdomPeriod;
use App\Models\EdomResponse;
use App\Models\EdomSettings;
use App\Services\Edom\EdomResponseMetadata;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EdomResponsesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => self::applyStudentGrouping($query))
            ->columns([
                TextColumn::make('student_name')
                    ->label('Nama Mahasiswa')
                    ->state(fn (EdomResponse $record): string => app(EdomResponseMetadata::class)->studentNameFor($record))
                    ->wrap(),
                TextColumn::make('student_nim')
                    ->label('NIM')
                    ->state(fn (EdomResponse $record): string => app(EdomResponseMetadata::class)->studentNimFor($record))
                    ->placeholder('-'),
                TextColumn::make('semester_label')
                    ->label('Semester')
                    ->state(fn (EdomResponse $record): string => app(EdomResponseMetadata::class)->semesterNameFor($record))
                    ->description(fn (EdomResponse $record): string => 'Tahun ajaran: '.app(EdomResponseMetadata::class)->tahunAjaranFor($record))
                    ->badge(),
                TextColumn::make('submitted_at')
                    ->label('Submit Terakhir')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('edomSettings.name')
                    ->label('Nama EDOM')
                    ->placeholder('-')
                    ->badge()
                    ->wrap(),
                TextColumn::make('filled_courses')
                    ->label('Mata Kuliah yang Sudah Diisi')
                    ->state(fn (EdomResponse $record): array => app(EdomResponseMetadata::class)->courseLabelsForStudentGroup($record))
                    ->bulleted()
                    ->listWithLineBreaks()
                    ->wrap(),
                TextColumn::make('filled_course_count')
                    ->label('Jumlah Mata Kuliah')
                    ->state(fn (EdomResponse $record): int => app(EdomResponseMetadata::class)->courseCountForStudentGroup($record))
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('edom_setting_id')
                    ->label('EDOM')
                    ->options(fn (): array => EdomSettings::query()->pluck('name', 'id')->all()),
                SelectFilter::make('edom_period_id')
                    ->label('Periode')
                    ->options(fn (): array => EdomPeriod::query()
                        ->orderByDesc('year')
                        ->orderBy('siakad_idsemester')
                        ->get()
                        ->mapWithKeys(fn (EdomPeriod $period): array => [
                            $period->id => $period->year.' / Semester '.$period->siakad_idsemester,
                        ])
                        ->all()),
            ])
            ->recordActions([
                Action::make('viewStudentResponses')
                    ->label('Lihat Detail Jawaban')
                    ->icon('heroicon-o-eye')
                    ->url(fn (EdomResponse $record): string => self::studentDetailUrl($record)),
            ])
            ->recordUrl(fn (EdomResponse $record): string => self::studentDetailUrl($record))
            ->toolbarActions([]);
    }

    public static function applyStudentGrouping(Builder $query): Builder
    {
        return $query
            ->select([
                'edom_response.siakad_idmahasiswa',
                'edom_response.edom_period_id',
                'edom_response.edom_setting_id',
            ])
            ->selectRaw('MIN(edom_response.id) as id')
            ->selectRaw('MAX(edom_response.submitted_at) as submitted_at')
            ->selectRaw('COUNT(DISTINCT edom_response.siakad_idmatakuliah) as filled_course_count')
            ->with(['period', 'edomSettings'])
            ->groupBy([
                'edom_response.siakad_idmahasiswa',
                'edom_response.edom_period_id',
                'edom_response.edom_setting_id',
            ])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id');
    }

    private static function studentDetailUrl(EdomResponse $record): string
    {
        return EdomResponseResource::getUrl('student-detail', [
            'studentId' => $record->siakad_idmahasiswa,
            'periodId' => $record->edom_period_id,
            'settingId' => $record->edom_setting_id,
        ]);
    }
}
