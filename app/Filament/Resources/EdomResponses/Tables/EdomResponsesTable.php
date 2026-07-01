<?php

namespace App\Filament\Resources\EdomResponses\Tables;

use App\Models\EdomResponse;
use App\Models\EdomSettings;
use App\Services\Edom\EdomResultAggregator;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EdomResponsesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => self::applyAggregationQuery($query))
            ->columns([
                TextColumn::make('edom_name')
                    ->label('EDOM')
                    ->placeholder('-'),
                TextColumn::make('periode')
                    ->label('Periode')
                    ->state(fn (EdomResponse $record): string => $record->siakad_idtahunajaran.' / Semester '.$record->siakad_idsemester)
                    ->badge(),
                TextColumn::make('course_label')
                    ->label('Kelas / Mata Kuliah')
                    ->state(fn (EdomResponse $record): string => app(EdomResultAggregator::class)->courseLabelFor($record))
                    ->description(fn (EdomResponse $record): ?string => app(EdomResultAggregator::class)->sectionMissingFor($record)
                        ? 'Data kelas tidak ditemukan pada /edom/penawaran.'
                        : null)
                    ->wrap(),
                TextColumn::make('dosen')
                    ->label('Dosen')
                    ->state(fn (EdomResponse $record): string => app(EdomResultAggregator::class)->dosenNameFor($record))
                    ->description(fn (EdomResponse $record): ?string => app(EdomResultAggregator::class)->dosenTeamFor($record) ?: null)
                    ->wrap(),
                TextColumn::make('category_name')
                    ->label('Kategori')
                    ->placeholder('Tanpa kategori')
                    ->badge()
                    ->wrap(),
                TextColumn::make('question_statement')
                    ->label('Pertanyaan')
                    ->placeholder('Pertanyaan tidak ditemukan')
                    ->limit(120)
                    ->wrap(),
                TextColumn::make('respondent_count')
                    ->label('Responden')
                    ->numeric()
                    ->badge(),
                TextColumn::make('answer_count')
                    ->label('Jawaban')
                    ->numeric()
                    ->badge(),
                TextColumn::make('average_score')
                    ->label('Rata-rata Nilai')
                    ->state(fn (EdomResponse $record): string => $record->average_score === null
                        ? '-'
                        : number_format((float) $record->average_score, 2, ',', '.'))
                    ->badge()
                    ->color('success'),
            ])
            ->filters([
                SelectFilter::make('edom_setting_id')
                    ->label('EDOM')
                    ->options(fn (): array => EdomSettings::query()->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->where('edom_response.edom_setting_id', $data['value'])
                        : $query),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    private static function applyAggregationQuery(Builder $query): Builder
    {
        return $query
            ->join('edom_periods', 'edom_periods.id', '=', 'edom_response.edom_period_id')
            ->join('edom_settings', 'edom_settings.id', '=', 'edom_response.edom_setting_id')
            ->join('edom_response_detail', 'edom_response_detail.edom_response_id', '=', 'edom_response.id')
            ->leftJoin('edom_questions', 'edom_questions.id', '=', 'edom_response_detail.edom_question_id')
            ->leftJoin('edom_question_categories', 'edom_question_categories.id', '=', 'edom_questions.edom_question_category_id')
            ->leftJoin('edom_question_options', 'edom_question_options.id', '=', 'edom_response_detail.edom_option_id')
            ->select([
                'edom_response.edom_period_id',
                'edom_periods.year as siakad_idtahunajaran',
                'edom_periods.siakad_idsemester',
                'edom_response.edom_setting_id',
                'edom_settings.name as edom_name',
                'edom_response.siakad_idmatakuliah',
                'edom_response.siakad_idtawarmatakuliahdetail',
                'edom_questions.edom_question_category_id',
                'edom_question_categories.name as category_name',
                'edom_response_detail.edom_question_id',
                'edom_questions.statement as question_statement',
            ])
            ->selectRaw('MIN(edom_response_detail.id) as id')
            ->selectRaw('COUNT(DISTINCT edom_response.id) as respondent_count')
            ->selectRaw('COUNT(edom_response_detail.id) as answer_count')
            ->selectRaw('AVG(edom_question_options.score) as average_score')
            ->groupBy([
                'edom_response.edom_period_id',
                'edom_periods.year',
                'edom_periods.siakad_idsemester',
                'edom_response.edom_setting_id',
                'edom_settings.name',
                'edom_response.siakad_idmatakuliah',
                'edom_response.siakad_idtawarmatakuliahdetail',
                'edom_questions.edom_question_category_id',
                'edom_question_categories.name',
                'edom_response_detail.edom_question_id',
                'edom_questions.statement',
            ])
            ->orderByDesc('edom_periods.year')
            ->orderBy('edom_periods.siakad_idsemester')
            ->orderBy('edom_settings.name')
            ->orderBy('edom_response.siakad_idtawarmatakuliahdetail')
            ->orderBy('edom_question_categories.name')
            ->orderBy('edom_questions.statement');
    }
}
