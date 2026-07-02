<?php

namespace App\Filament\Resources\EdomReports\Pages;

use App\Filament\Resources\EdomReports\EdomReportResource;
use App\Models\EdomResponse;
use App\Models\ProgramStudi;
use App\Services\Edom\EdomKrsSectionSyncService;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListEdomReportCourses extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = EdomReportResource::class;

    protected string $view = 'filament.resources.edom-reports.pages.list-edom-report-courses';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        app(EdomKrsSectionSyncService::class)->syncKnownStudentPeriods();
    }

    public function getTitle(): string
    {
        return 'Mata Kuliah - '.$this->record->display_name;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->coursesQuery())
            ->columns([
                TextColumn::make('nama')
                    ->label('Mata Kuliah')
                    ->state(fn (EdomResponse $record): string => $this->courseNameFor($record))
                    ->description(fn (EdomResponse $record): string => $this->courseLabelFor($record))
                    ->wrap(),
                TextColumn::make('kode')
                    ->label('Kode')
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('siakad_idmatakuliah')
                    ->label('ID Mata Kuliah')
                    ->badge(),
                TextColumn::make('siakad_idtawarmatakuliahdetail')
                    ->label('ID Detail Penawaran')
                    ->badge(),
                TextColumn::make('respondent_count')
                    ->label('Mahasiswa Mengisi')
                    ->badge()
                    ->color('info'),
                TextColumn::make('response_count')
                    ->label('Respons')
                    ->state(fn (EdomResponse $record): int => EdomReportResource::responseCountForProgramStudiAndCourse(
                        $this->record,
                        EdomReportResource::courseKeyForCourseId($record->siakad_idmatakuliah),
                    ))
                    ->badge()
                    ->color('success'),
            ])
            ->recordActions([
                Action::make('report')
                    ->label('Lihat Report')
                    ->icon('heroicon-o-chart-bar-square')
                    ->url(fn (EdomResponse $record): string => EdomReportResource::getUrl('course-report', [
                        'record' => $this->record,
                        'courseKey' => EdomReportResource::courseKeyForCourseId($record->siakad_idmatakuliah),
                    ])),
            ])
            ->recordUrl(fn (EdomResponse $record): string => EdomReportResource::getUrl('course-report', [
                'record' => $this->record,
                'courseKey' => EdomReportResource::courseKeyForCourseId($record->siakad_idmatakuliah),
            ]))
            ->toolbarActions([]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToProgramStudis')
                ->label('Kembali ke Program Studi')
                ->icon('heroicon-o-arrow-left')
                ->url(EdomReportResource::getUrl('index')),
        ];
    }

    private function coursesQuery(): Builder
    {
        /** @var ProgramStudi $programStudi */
        $programStudi = $this->record;

        if ($programStudi->id_unw_program_studi === null) {
            return EdomResponse::query()->whereRaw('1 = 0');
        }

        return EdomResponse::query()
            ->leftJoin('edom_periods', 'edom_periods.id', '=', 'edom_response.edom_period_id')
            ->leftJoin('edom_krs_sections', function ($join): void {
                $join->on('edom_krs_sections.siakad_idmahasiswa', '=', 'edom_response.siakad_idmahasiswa')
                    ->on('edom_krs_sections.siakad_idtahunajaran', '=', 'edom_periods.year')
                    ->on('edom_krs_sections.siakad_idsemester', '=', 'edom_periods.siakad_idsemester')
                    ->on('edom_krs_sections.idmatakuliah', '=', 'edom_response.siakad_idmatakuliah');
            })
            ->where('edom_response.id_unw_program_studi', (int) $programStudi->id_unw_program_studi)
            ->select([
                'edom_response.siakad_idmatakuliah',
            ])
            ->selectRaw('MIN(edom_response.id) as id')
            ->selectRaw('MIN(edom_response.siakad_idtawarmatakuliahdetail) as siakad_idtawarmatakuliahdetail')
            ->selectRaw('MIN(edom_krs_sections.kode) as kode')
            ->selectRaw('MIN(edom_krs_sections.nama) as nama')
            ->selectRaw('COUNT(DISTINCT edom_response.siakad_idmahasiswa) as respondent_count')
            ->selectRaw('COUNT(DISTINCT edom_response.id) as response_count')
            ->groupBy([
                'edom_response.siakad_idmatakuliah',
            ])
            ->orderBy('kode')
            ->orderBy('nama')
            ->orderBy('edom_response.siakad_idmatakuliah');
    }

    private function courseNameFor(EdomResponse $record): string
    {
        $name = trim((string) $record->getAttribute('nama'));

        return $name !== '' ? $name : 'Mata kuliah #'.$record->siakad_idmatakuliah;
    }

    private function courseLabelFor(EdomResponse $record): string
    {
        $code = trim((string) $record->getAttribute('kode'));
        $name = $this->courseNameFor($record);

        return trim($code.' - '.$name, ' -') ?: 'Mata kuliah #'.$record->siakad_idmatakuliah;
    }
}
