<?php

namespace App\Filament\Resources\EdomPeriods\Schemas;

use App\Models\EdomSettings;
use App\Services\Siakad\UnwApiSiakad;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Throwable;

class EdomPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('year')
                ->label('Tahun Ajaran')
                ->numeric()
                ->integer()
                ->default((int) now()->year)
                ->required()
                ->minValue(2000)
                ->maxValue(2100)
                ->helperText('Input angka tahun ajaran, misalnya 2026 untuk 2026/2027.'),

            Select::make('siakad_idsemester')
                ->label('Semester SIAKAD')
                ->options(fn (): array => self::semesterOptions())
                ->searchable()
                ->preload()
                ->native(false)
                ->required()
                ->placeholder('Pilih semester dari SIAKAD')
                ->helperText('Daftar semester diambil melalui /edom/semester.'),

            Select::make('settings')
                ->label('EDOM Settings')
                ->relationship('settings', 'name')
                ->multiple()
                ->searchable()
                ->preload()
                ->helperText('Pilih set pertanyaan yang berlaku pada periode ini.'),

            Select::make('status')
                ->label('Status EDOM Settings')
                ->options(EdomSettings::statusOptions())
                ->default(EdomSettings::STATUS_DRAFT)
                ->native(false)
                ->required()
                ->helperText('Status ini mengubah status EDOM Settings yang dipilih pada periode ini, bukan membuat status periode baru.'),
        ]);
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
