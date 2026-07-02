<?php

namespace App\Filament\Resources\EdomReports;

use App\Filament\Resources\EdomReports\Pages\ListEdomReportCourses;
use App\Filament\Resources\EdomReports\Pages\ListEdomReports;
use App\Filament\Resources\EdomReports\Pages\ViewEdomCourseReport;
use App\Models\EdomResponse;
use App\Models\ProgramStudi;
use App\Services\Siakad\UnwApiSiakad;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class EdomReportResource extends Resource
{
    protected static ?string $model = ProgramStudi::class;

    protected static string|\UnitEnum|null $navigationGroup = 'EDOM';

    protected static ?string $navigationLabel = 'EDOM Reports';

    protected static ?string $modelLabel = 'EDOM Report';

    protected static ?string $pluralModelLabel = 'EDOM Reports';

    protected static ?string $slug = 'edom-reports';

    protected static ?int $navigationSort = 30;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $recordTitleAttribute = 'nama';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->orderBy('jenjang_nama_singkat')->orderBy('nama'))
            ->columns([
                TextColumn::make('display_name')
                    ->label('Program Studi')
                    ->state(fn (ProgramStudi $record): string => $record->display_name)
                    ->searchable(['nama', 'jenjang', 'jenjang_nama_singkat'])
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->orderBy('jenjang_nama_singkat', $direction)
                        ->orderBy('nama', $direction)),
                TextColumn::make('nama_fakultas')
                    ->label('Fakultas')
                    ->placeholder('-')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('course_count')
                    ->label('Jumlah Mata Kuliah')
                    ->state(fn (ProgramStudi $record): int => self::courseCountForProgramStudi($record))
                    ->badge()
                    ->color('info'),
                TextColumn::make('response_count')
                    ->label('Jumlah Respons')
                    ->state(fn (ProgramStudi $record): int => self::responseCountForProgramStudi($record))
                    ->badge()
                    ->color('success'),
            ])
            ->recordActions([
                Action::make('courses')
                    ->label('Lihat Mata Kuliah')
                    ->icon('heroicon-o-academic-cap')
                    ->url(fn (ProgramStudi $record): string => static::getUrl('courses', ['record' => $record])),
            ])
            ->recordUrl(fn (ProgramStudi $record): string => static::getUrl('courses', ['record' => $record]))
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEdomReports::route('/'),
            'courses' => ListEdomReportCourses::route('/{record}/courses'),
            'course-report' => ViewEdomCourseReport::route('/{record}/courses/{courseKey}'),
        ];
    }

    public static function settingIdsForProgramStudi(ProgramStudi $programStudi)
    {
        return DB::table('edom_settings_program_studi')
            ->where('program_studi_id', $programStudi->id)
            ->pluck('edom_setting_id')
            ->map(fn ($id): int => (int) $id)
            ->values();
    }

    public static function courseKeyForResponse(EdomResponse $response): string
    {
        $sectionId = (int) $response->siakad_idtawarmatakuliahdetail;

        if ($sectionId > 0) {
            return 'd_'.$sectionId;
        }

        return 'm_'.((int) $response->siakad_idmatakuliah);
    }

    public static function courseCountForProgramStudi(ProgramStudi $programStudi): int
    {
        $idUnwProgramStudi = $programStudi->id_unw_program_studi;
        $settingIds = static::settingIdsForProgramStudi($programStudi);

        if ($idUnwProgramStudi === null || $settingIds->isEmpty()) {
            return 0;
        }

        return Cache::remember(
            'edom-report:krs-course-count:program-studi:'.$programStudi->id,
            now()->addMinutes(30),
            function () use ($idUnwProgramStudi, $settingIds): int {
                $studentPeriods = EdomResponse::query()
                    ->join('edom_periods', 'edom_periods.id', '=', 'edom_response.edom_period_id')
                    ->whereIn('edom_response.edom_setting_id', $settingIds)
                    ->select([
                        'edom_response.siakad_idmahasiswa',
                        'edom_periods.year as siakad_idtahunajaran',
                        'edom_periods.siakad_idsemester',
                    ])
                    ->distinct()
                    ->get();

                if ($studentPeriods->isEmpty()) {
                    return 0;
                }

                $courseIds = collect();

                foreach ($studentPeriods as $studentPeriod) {
                    try {
                        $sections = Cache::remember(
                            'edom-report:krs:'
                                .$studentPeriod->siakad_idmahasiswa.':'
                                .$studentPeriod->siakad_idtahunajaran.':'
                                .$studentPeriod->siakad_idsemester,
                            now()->addMinutes(30),
                            fn (): array => app(UnwApiSiakad::class)->krs(
                                $studentPeriod->siakad_idmahasiswa,
                                $studentPeriod->siakad_idtahunajaran,
                                $studentPeriod->siakad_idsemester,
                            )
                        );
                    } catch (Throwable $exception) {
                        report($exception);

                        continue;
                    }

                    collect($sections)
                        ->filter(fn ($section): bool => is_array($section))
                        ->filter(fn (array $section): bool => (string) data_get($section, 'id_unw_program_studi') === (string) $idUnwProgramStudi)
                        ->pluck('idmatakuliah')
                        ->filter(fn ($id): bool => $id !== null && $id !== '')
                        ->each(fn ($id) => $courseIds->push((string) $id));
                }

                return $courseIds->unique()->count();
            }
        );
    }

    public static function responseCountForProgramStudi(ProgramStudi $programStudi): int
    {
        $settingIds = static::settingIdsForProgramStudi($programStudi);

        if ($settingIds->isEmpty()) {
            return 0;
        }

        return EdomResponse::query()
            ->whereIn('edom_setting_id', $settingIds)
            ->count();
    }
}
