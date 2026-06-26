<?php

namespace App\Filament\Resources\EdomPeriods\Schemas;

use App\Services\Siakad\UnwApiSiakad;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Throwable;

class EdomPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('year')
                    ->label('Tahun Ajaran / siakad_idtahunajaran')
                    ->numeric()
                    ->required(),

                Select::make('siakad_idsemester')
                    ->label('Semester')
                    ->options(fn (): array => self::semesterOptions())
                    ->searchable()
                    ->required(),
            ]);
    }

    private static function semesterOptions(): array
    {
        try {
            return collect(app(UnwApiSiakad::class)->semester())
                ->mapWithKeys(function (array $semester): array {
                    $id = $semester['id'] ?? $semester['siakad_idsemester'] ?? null;
                    $label = $semester['nama'] ?? $semester['name'] ?? $semester['semester'] ?? $id;

                    return $id === null ? [] : [(string) $id => (string) $label];
                })
                ->all();
        } catch (Throwable) {
            return [
                '1' => 'Gasal',
                '2' => 'Genap',
                '3' => 'Antara',
            ];
        }
    }
}
