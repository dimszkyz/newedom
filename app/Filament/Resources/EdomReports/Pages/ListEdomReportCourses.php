<?php

namespace App\Filament\Resources\EdomReports\Pages;

use App\Filament\Resources\EdomReports\EdomReportResource;
use App\Models\EdomResponse;
use App\Models\ProgramStudi;
use App\Services\Edom\EdomResponseMetadata;
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
                TextColumn::make('course_name')
                    ->label('Mata Kuliah')
                    ->state(fn (EdomResponse $record): string => app(EdomResponseMetadata::class)->courseNameFor($record))
                    ->description(fn (EdomResponse $record): string => app(EdomResponseMetadata::class)->courseLabelFor($record))
                    ->wrap(),
                TextColumn::make('siakad_idmatakuliah')
                    ->label('ID Mata Kuliah')
                    ->badge(),
                TextColumn::make('siakad_idtawarmatakuliahdetail')
                    ->label('ID Detail Penawaran')
                    ->badge(),
                TextColumn::make('respondent_count')
                    ->label('Mahasiswa')
                    ->badge()
                    ->color('info'),
                TextColumn::make('response_count')
                    ->label('Respons')
                    ->badge()
                    ->color('success'),
            ])
            ->recordActions([
                Action::make('report')
                    ->label('Lihat Report')
                    ->icon('heroicon-o-chart-bar-square')
                    ->url(fn (EdomResponse $record): string => EdomReportResource::getUrl('course-report', [
                        'record' => $this->record,
                        'courseKey' => EdomReportResource::courseKeyForResponse($record),
                    ])),
            ])
            ->recordUrl(fn (EdomResponse $record): string => EdomReportResource::getUrl('course-report', [
                'record' => $this->record,
                'courseKey' => EdomReportResource::courseKeyForResponse($record),
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
        $settingIds = EdomReportResource::settingIdsForProgramStudi($programStudi);

        return EdomResponse::query()
            ->whereIn('edom_setting_id', $settingIds->all())
            ->select([
                'edom_response.siakad_idmatakuliah',
                'edom_response.siakad_idtawarmatakuliahdetail',
            ])
            ->selectRaw('MIN(edom_response.id) as id')
            ->selectRaw('MIN(edom_response.edom_period_id) as edom_period_id')
            ->selectRaw('MIN(edom_response.edom_setting_id) as edom_setting_id')
            ->selectRaw('COUNT(*) as response_count')
            ->selectRaw('COUNT(DISTINCT edom_response.siakad_idmahasiswa) as respondent_count')
            ->groupBy([
                'edom_response.siakad_idmatakuliah',
                'edom_response.siakad_idtawarmatakuliahdetail',
            ])
            ->orderBy('edom_response.siakad_idmatakuliah');
    }
}
