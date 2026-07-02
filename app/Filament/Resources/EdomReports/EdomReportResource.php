<?php

namespace App\Filament\Resources\EdomReports;

use App\Filament\Resources\EdomReports\Pages\ListEdomReportCourses;
use App\Filament\Resources\EdomReports\Pages\ListEdomReports;
use App\Filament\Resources\EdomReports\Pages\ViewEdomCourseReport;
use App\Models\EdomKrsSection;
use App\Models\EdomResponse;
use App\Models\ProgramStudi;
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
                    ->description('Berdasarkan idmatakuliah unik dari edom_response')
                    ->badge()
                    ->color('info'),
                TextColumn::make('response_count')
                    ->label('Jumlah Respons')
                    ->state(fn (ProgramStudi $record): int => self::responseCountForProgramStudi($record))
                    ->description('Berdasarkan relasi edom_response.id_unw_program_studi')
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

        return $sectionId > 0 ? 'd_'.$sectionId : static::courseKeyForCourseId($response->siakad_idmatakuliah);
    }

    public static function courseKeyForCourseId(int|string $courseId): string
    {
        return 'm_'.((int) $courseId);
    }

    public static function courseKeyForKrsSection(EdomKrsSection $section): string
    {
        return static::courseKeyForCourseId($section->idmatakuliah);
    }

    public static function courseCountForProgramStudi(ProgramStudi $programStudi): int
    {
        if ($programStudi->id_unw_program_studi === null) {
            return 0;
        }

        return EdomResponse::query()
            ->where('id_unw_program_studi', (int) $programStudi->id_unw_program_studi)
            ->distinct('siakad_idmatakuliah')
            ->count('siakad_idmatakuliah');
    }

    public static function responseCountForProgramStudi(ProgramStudi $programStudi): int
    {
        if ($programStudi->id_unw_program_studi === null) {
            return 0;
        }

        return EdomResponse::query()
            ->where('id_unw_program_studi', (int) $programStudi->id_unw_program_studi)
            ->count();
    }

    public static function responseCountForProgramStudiAndCourse(ProgramStudi $programStudi, string $courseKey): int
    {
        if ($programStudi->id_unw_program_studi === null) {
            return 0;
        }

        $query = EdomResponse::query()
            ->where('id_unw_program_studi', (int) $programStudi->id_unw_program_studi);

        if (str_starts_with($courseKey, 'd_')) {
            $query->where('siakad_idtawarmatakuliahdetail', (int) substr($courseKey, 2));
        } elseif (str_starts_with($courseKey, 'm_')) {
            $query->where('siakad_idmatakuliah', (int) substr($courseKey, 2));
        } else {
            return 0;
        }

        return $query->count();
    }
}
