<?php

namespace App\Filament\Resources\EdomResponses\Tables;

use App\Filament\Resources\EdomResponses\EdomResponseResource;
use App\Models\EdomPeriod;
use App\Models\EdomResponse;
use App\Models\EdomSettings;
use App\Services\Edom\EdomResponseMetadata;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EdomResponsesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['period', 'edomSettings', 'details.questionOption'])
                ->withCount('details')
                ->latest('submitted_at')
                ->latest('id'))
            ->columns([
                TextColumn::make('student_name')
                    ->label('Nama Mahasiswa')
                    ->state(fn (EdomResponse $record): string => app(EdomResponseMetadata::class)->studentNameFor($record))
                    ->description(fn (EdomResponse $record): string => 'ID SIAKAD: '.$record->siakad_idmahasiswa)
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
                    ->label('Waktu Submit')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('edomSettings.name')
                    ->label('Nama EDOM')
                    ->placeholder('-')
                    ->badge()
                    ->wrap(),
                TextColumn::make('course_label')
                    ->label('EDOM Mata Kuliah')
                    ->state(fn (EdomResponse $record): string => app(EdomResponseMetadata::class)->courseLabelFor($record))
                    ->description(function (EdomResponse $record): ?string {
                        $metadata = app(EdomResponseMetadata::class);

                        return $metadata->sectionMissingFor($record)
                            ? 'Data mata kuliah tidak ditemukan pada /edom/penawaran.'
                            : 'Dosen: '.$metadata->dosenNameFor($record);
                    })
                    ->wrap(),
                TextColumn::make('details_count')
                    ->label('Jumlah Jawaban')
                    ->counts('details')
                    ->badge(),
                TextColumn::make('average_score')
                    ->label('Rata-rata Nilai')
                    ->state(fn (EdomResponse $record): string => app(EdomResponseMetadata::class)->formattedAverageScoreFor($record))
                    ->badge()
                    ->color('success'),
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
                ViewAction::make()
                    ->label('Lihat Detail'),
            ])
            ->recordUrl(fn (EdomResponse $record): string => EdomResponseResource::getUrl('view', ['record' => $record]))
            ->toolbarActions([]);
    }
}
