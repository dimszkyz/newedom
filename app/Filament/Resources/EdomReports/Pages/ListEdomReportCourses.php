<?php

namespace App\Filament\Resources\EdomReports\Pages;

use App\Filament\Resources\EdomReports\EdomReportResource;
use App\Models\EdomResponse;
use App\Models\ProgramStudi;
use App\Services\Edom\EdomCourseReportExporter;
use App\Services\Edom\EdomKrsReportData;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListEdomReportCourses extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = EdomReportResource::class;

    protected string $view = 'filament.pages.table-page';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        app(EdomKrsReportData::class)->refreshKnownResponseMetadata();
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
                    ->wrap(),
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
                Action::make('exportExcel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(fn (EdomResponse $record): StreamedResponse => app(EdomCourseReportExporter::class)->export(
                        $this->record,
                        EdomReportResource::courseKeyForCourseId($record->siakad_idmatakuliah),
                    )),
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

        return EdomReportResource::coursesForProgramStudi($programStudi);
    }
}
