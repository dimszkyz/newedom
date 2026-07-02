<?php

namespace App\Filament\Resources\EdomResponses\Pages;

use App\Filament\Resources\EdomResponses\EdomResponseResource;
use App\Models\EdomResponse;
use App\Models\EdomResponseDetail;
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

    protected string $view = 'filament.resources.edom-responses.pages.view-student-edom-responses';

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

        return 'Detail Jawaban - '.app(EdomResponseMetadata::class)->studentNameFor($response);
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
            ->query($this->detailsQuery())
            ->columns([
                TextColumn::make('course_name')
                    ->label('Mata Kuliah')
                    ->state(fn (EdomResponseDetail $record): string => app(EdomResponseMetadata::class)
                        ->krsCourseLabelFor($record->response))
                    ->wrap(),
                TextColumn::make('category_name_for_display')
                    ->label('Kategori')
                    ->state(fn (EdomResponseDetail $record): string => $record->category_name_for_display)
                    ->badge()
                    ->wrap(),
                TextColumn::make('question_statement_for_display')
                    ->label('Pernyataan')
                    ->state(fn (EdomResponseDetail $record): string => $record->question_statement_for_display)
                    ->wrap(),
                TextColumn::make('option_name_for_display')
                    ->label('Jawaban')
                    ->state(fn (EdomResponseDetail $record): ?string => $record->option_name_for_display)
                    ->placeholder('-')
                    ->badge(),
                TextColumn::make('option_score_for_display')
                    ->label('Nilai')
                    ->state(fn (EdomResponseDetail $record): ?int => $record->option_score_for_display)
                    ->placeholder('-')
                    ->badge()
                    ->color('success'),
                TextColumn::make('answer_text')
                    ->label('Jawaban Teks')
                    ->placeholder('-')
                    ->wrap(),
                TextColumn::make('response.submitted_at')
                    ->label('Waktu Submit')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->recordActions([])
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

    private function detailsQuery(): Builder
    {
        return self::detailsForStudentGroup(
            $this->studentId,
            $this->periodId,
            $this->settingId,
        );
    }

    public static function detailsForStudentGroup(
        string $studentId,
        int $periodId,
        int $settingId,
    ): Builder {
        return EdomResponseDetail::query()
            ->whereHas('response', fn (Builder $query): Builder => $query
                ->where('siakad_idmahasiswa', $studentId)
                ->where('edom_period_id', $periodId)
                ->where('edom_setting_id', $settingId))
            ->with([
                'response.period',
                'response.edomSettings',
                'question.category',
                'questionOption',
            ])
            ->orderBy('edom_response_id')
            ->orderBy('id');
    }

    private function responsesQuery(): Builder
    {
        return $this->applyResponseScope(
            EdomResponse::query()->with(['period', 'edomSettings'])
        );
    }

    private function applyResponseScope(Builder $query): Builder
    {
        return $query
            ->where('siakad_idmahasiswa', $this->studentId)
            ->where('edom_period_id', $this->periodId)
            ->where('edom_setting_id', $this->settingId);
    }

    private function representativeResponse(): EdomResponse
    {
        return $this->responsesQuery()->latest('submitted_at')->firstOrFail();
    }
}
