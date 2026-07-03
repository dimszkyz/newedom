<?php

namespace App\Filament\Resources\EdomResponses\Pages;

use App\Filament\Resources\EdomResponses\EdomResponseResource;
use App\Models\EdomResponse;
use App\Services\Edom\EdomResponseMetadata;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewEdomResponse extends ViewRecord
{
    protected static string $resource = EdomResponseResource::class;

    public function getTitle(): string
    {
        /** @var EdomResponse $record */
        $record = $this->getRecord();

        return 'Detail Jawaban - '.app(EdomResponseMetadata::class)->krsCourseLabelFor($record);
    }

    public function getSubheading(): ?string
    {
        /** @var EdomResponse $record */
        $record = $this->getRecord();
        $metadata = app(EdomResponseMetadata::class);

        return implode(' | ', [
            $metadata->studentNameFor($record),
            'NIM '.$metadata->studentNimFor($record),
            $metadata->semesterNameFor($record),
            'Tahun Ajaran '.$metadata->tahunAjaranFor($record),
        ]);
    }

    protected function getHeaderActions(): array
    {
        /** @var EdomResponse $record */
        $record = $this->getRecord();

        return [
            Action::make('backToStudentCourses')
                ->label('Kembali ke Daftar Mata Kuliah')
                ->icon('heroicon-o-arrow-left')
                ->url(EdomResponseResource::getUrl('student-detail', [
                    'studentId' => $record->siakad_idmahasiswa,
                    'periodId' => $record->edom_period_id,
                    'settingId' => $record->edom_setting_id,
                ])),
        ];
    }
}
