<?php

namespace App\Filament\Resources\EdomPeriods\Schemas;

use App\Services\Siakad\UnwApiSiakad;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Throwable;

class EdomPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('year')
                ->label('Tahun Ajaran SIAKAD')
                ->options(self::yearOptions())
                ->default((int) now()->year)
                ->searchable()
                ->native(false)
                ->required()
                ->helperText('Nilai yang disimpan adalah ID tahun awal, misalnya 2026 untuk 2026/2027.'),

            Select::make('siakad_idsemester')
                ->label('Semester SIAKAD')
                ->options(fn (): array => self::semesterOptions())
                ->searchable()
                ->preload()
                ->native(false)
                ->required()
                ->placeholder('Pilih semester dari API SIAKAD')
                ->helperText('Daftar semester diambil melalui GET /edom/semester.'),
        ]);
    }

    public static function yearOptions(): array
    {
        $currentYear = (int) now()->year;

        return collect(range($currentYear + 1, $currentYear - 5))
            ->mapWithKeys(fn (int $year): array => [
                $year => $year.'/'.($year + 1),
            ])
            ->all();
    }

    public static function semesterOptions(): array
    {
        try {
            return collect(app(UnwApiSiakad::class)->semester())
                ->filter(fn (mixed $semester): bool => is_array($semester)
                    && (int) ($semester['id'] ?? 0) > 0)
                ->mapWithKeys(function (array $semester): array {
                    $id = (int) $semester['id'];
                    $name = trim((string) ($semester['nama'] ?? ''));

                    return [$id => $name !== '' ? $name : 'Semester '.$id];
                })
                ->all();
        } catch (Throwable $exception) {
            report($exception);

            return [];
        }
    }
}
