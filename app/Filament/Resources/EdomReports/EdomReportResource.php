<?php

namespace App\Filament\Resources\EdomReports;

use App\Filament\Resources\EdomReports\Pages\ListEdomReportCourses;
use App\Filament\Resources\EdomReports\Pages\ListEdomReports;
use App\Filament\Resources\EdomReports\Pages\ViewEdomCourseReport;
use App\Models\EdomResponse;
use App\Models\ProgramStudi;
use App\Services\Edom\EdomKrsReportData;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
                TextColumn::make('nama_fakultas')->label('Fakultas')->placeholder('-')->searchable()->wrap(),
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

    public static function courseKeyForResponse(EdomResponse $response): string
    {
        $sectionId = (int) $response->siakad_idtawarmatakuliahdetail;

        return $sectionId > 0 ? 'd_'.$sectionId : self::courseKeyForCourseId($response->siakad_idmatakuliah);
    }

    public static function courseKeyForCourseId(int|string|null $courseId): string
    {
        return 'm_'.((int) $courseId);
    }

    public static function courseCountForProgramStudi(ProgramStudi $programStudi): int
    {
        $krsCourseCount = app(EdomKrsReportData::class)->courseCountForProgramStudi($programStudi);

        if ($krsCourseCount > 0) {
            return $krsCourseCount;
        }

        return static::responsesForProgramStudi($programStudi)
            ->distinct('siakad_idmatakuliah')
            ->count('siakad_idmatakuliah');
    }

    public static function responseCountForProgramStudi(ProgramStudi $programStudi): int
    {
        return static::responsesForProgramStudi($programStudi)->count();
    }

    public static function responseCountForProgramStudiAndCourse(ProgramStudi $programStudi, string $courseKey): int
    {
        $query = static::responsesForProgramStudi($programStudi);

        if (str_starts_with($courseKey, 'd_')) {
            $query->where('siakad_idtawarmatakuliahdetail', (int) substr($courseKey, 2));
        } elseif (str_starts_with($courseKey, 'm_')) {
            $query->where('siakad_idmatakuliah', (int) substr($courseKey, 2));
        } else {
            return 0;
        }

        return $query->count();
    }

    public static function responsesForProgramStudi(ProgramStudi $programStudi): Builder
    {
        $query = EdomResponse::query();

        if ($programStudi->id_unw_program_studi === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(
            'id_unw_program_studi',
            (int) $programStudi->id_unw_program_studi,
        );
    }

    public static function coursesForProgramStudi(ProgramStudi $programStudi): Builder
    {
        $rows = app(EdomKrsReportData::class)->reportCourseRowsForProgramStudi($programStudi);

        if ($rows->isEmpty()) {
            return static::responsesForProgramStudi($programStudi)
                ->whereNotNull('siakad_idmatakuliah')
                ->selectRaw('MIN(id) as id')
                ->selectRaw('siakad_idmatakuliah')
                ->selectRaw('MIN(siakad_idtawarmatakuliahdetail) as siakad_idtawarmatakuliahdetail')
                ->selectRaw('siakad_idmatakuliah as idmatakuliah')
                ->selectRaw('MIN(siakad_idtawarmatakuliahdetail) as idtawarmatakuliahdetail')
                ->selectRaw('NULL as kode')
                ->selectRaw("CONCAT('Mata kuliah #', siakad_idmatakuliah) as nama")
                ->selectRaw("CONCAT('Mata kuliah #', siakad_idmatakuliah) as course_label")
                ->selectRaw('COUNT(DISTINCT siakad_idmahasiswa) as krs_student_count')
                ->groupBy('siakad_idmatakuliah')
                ->orderBy('siakad_idmatakuliah');
        }

        $union = null;

        foreach ($rows as $row) {
            $select = DB::query()
                ->selectRaw('? as id', [(int) $row['id']])
                ->selectRaw('? as siakad_idmatakuliah', [(int) $row['siakad_idmatakuliah']])
                ->selectRaw('? as siakad_idtawarmatakuliahdetail', [$row['siakad_idtawarmatakuliahdetail']])
                ->selectRaw('? as idmatakuliah', [(int) $row['idmatakuliah']])
                ->selectRaw('? as idtawarmatakuliahdetail', [$row['idtawarmatakuliahdetail']])
                ->selectRaw('? as kode', [$row['kode']])
                ->selectRaw('? as nama', [$row['nama']])
                ->selectRaw('? as course_label', [$row['course_label']])
                ->selectRaw('? as krs_student_count', [(int) $row['krs_student_count']]);

            $union = $union === null ? $select : $union->unionAll($select);
        }

        return EdomResponse::query()
            ->fromSub($union, 'edom_response')
            ->select('edom_response.*')
            ->orderBy('kode')
            ->orderBy('nama');
    }
}
