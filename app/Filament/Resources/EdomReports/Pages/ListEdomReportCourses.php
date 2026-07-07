<?php

namespace App\Filament\Resources\EdomReports\Pages;

use App\Filament\Resources\EdomReports\EdomReportResource;
use App\Models\EdomKrsSection;
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

    protected string $view = 'filament.pages.table-page';

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
                    ->description(fn (EdomKrsSection $record): string => $record->course_label)
                    ->wrap(),
                TextColumn::make('kode')
                    ->label('Kode')
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('idmatakuliah')
                    ->label('ID Mata Kuliah')
                    ->badge(),
                TextColumn::make('idtawarmatakuliahdetail')
                    ->label('ID Detail Penawaran')
                    ->badge(),
                TextColumn::make('krs_student_count')
                    ->label('Mahasiswa KRS')
                    ->badge()
                    ->color('info'),
                TextColumn::make('response_count')
                    ->label('Respons')
                    ->state(fn (EdomKrsSection $record): int => EdomReportResource::responseCountForProgramStudiAndCourse(
                        $this->record,
                        EdomReportResource::courseKeyForKrsSection($record),
                    ))
                    ->badge()
                    ->color('success'),
            ])
            ->recordActions([
                Action::make('report')
                    ->label('Lihat Report')
                    ->icon('heroicon-o-chart-bar-square')
                    ->url(fn (EdomKrsSection $record): string => EdomReportResource::getUrl('course-report', [
                        'record' => $this->record,
                        'courseKey' => EdomReportResource::courseKeyForKrsSection($record),
                    ])),
            ])
            ->recordUrl(fn (EdomKrsSection $record): string => EdomReportResource::getUrl('course-report', [
                'record' => $this->record,
                'courseKey' => EdomReportResource::courseKeyForKrsSection($record),
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

        return EdomReportResource::coursesForProgramStudi($programStudi);
    }
}
