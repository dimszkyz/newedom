<?php

namespace App\Filament\Resources\EdomResponses\Pages;

use App\Filament\Resources\EdomResponses\EdomResponseResource;
use App\Models\EdomResponse;
use App\Services\Edom\EdomResponseMetadata;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ViewStudentEdomResponses extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = EdomResponseResource::class;

    protected string $view = 'filament.pages.table-page';

    public string $studentId;

    public int $periodId;

    public int $settingId;

    public function mount(string $studentId, int $periodId, int $settingId): void
    {
        $this->studentId = $studentId;
        $this->periodId = $periodId;
        $this->settingId = $settingId;

        abort_unless($this->responsesQuery()->exists(), 404);
    }

    public function getTitle(): string
    {
        $response = $this->representativeResponse();

        return 'EDOM Mata Kuliah - '.app(EdomResponseMetadata::class)->studentNameFor($response);
    }

    public function getSubheading(): ?string
    {
        $response = $this->representativeResponse();
        $metadata = app(EdomResponseMetadata::class);

        return implode(' | ', [
            'NIM '.$metadata->studentNimFor($response),
            $metadata->semesterNameFor($response),
            'Tahun Ajaran '.$metadata->tahunAjaranFor($response),
            (string) $response->edomSettings?->name,
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->responsesQuery())
            ->columns([
                TextColumn::make('course_name')
                    ->label('Mata Kuliah')
                    ->state(fn (EdomResponse $record): string => app(EdomResponseMetadata::class)
                        ->krsCourseLabelFor($record))
                    ->url(fn (EdomResponse $record): string => EdomResponseResource::getUrl('view', [
                        'record' => $record,
                    ]))
                    ->color('primary')
                    ->wrap(),
                TextColumn::make('details_count')
                    ->label('Jumlah Jawaban')
                    ->badge(),
                TextColumn::make('average_score')
                    ->label('Rata-rata Nilai')
                    ->state(fn (EdomResponse $record): string => app(EdomResponseMetadata::class)
                        ->formattedAverageScoreFor($record))
                    ->badge()
                    ->color('success'),
                TextColumn::make('submitted_at')
                    ->label('Waktu Submit')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('viewResponse')
                    ->label('Lihat Detail Jawaban')
                    ->icon('heroicon-o-eye')
                    ->url(fn (EdomResponse $record): string => EdomResponseResource::getUrl('view', [
                        'record' => $record,
                    ])),
            ])
            ->recordUrl(fn (EdomResponse $record): string => EdomResponseResource::getUrl('view', [
                'record' => $record,
            ]))
            ->toolbarActions([]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToResponses')
                ->label('Kembali ke EDOM Response')
                ->icon('heroicon-o-arrow-left')
                ->url(EdomResponseResource::getUrl('index')),
        ];
    }

    public static function responsesForStudentGroup(
        string $studentId,
        int $periodId,
        int $settingId,
    ): Builder {
        return EdomResponse::query()
            ->where('siakad_idmahasiswa', $studentId)
            ->where('edom_period_id', $periodId)
            ->where('edom_setting_id', $settingId)
            ->with([
                'period',
                'edomSettings',
                'details.questionOption',
            ])
            ->withCount('details')
            ->orderByDesc('submitted_at')
            ->orderByDesc('id');
    }

    private function responsesQuery(): Builder
    {
        return self::responsesForStudentGroup(
            $this->studentId,
            $this->periodId,
            $this->settingId,
        );
    }

    private function representativeResponse(): EdomResponse
    {
        return $this->responsesQuery()->latest('submitted_at')->firstOrFail();
    }
}
