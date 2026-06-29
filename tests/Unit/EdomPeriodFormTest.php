<?php

namespace Tests\Unit;

use App\Filament\Resources\EdomPeriods\Schemas\EdomPeriodForm;
use App\Services\Siakad\UnwApiSiakad;
use Mockery;
use Tests\TestCase;

class EdomPeriodFormTest extends TestCase
{
    public function test_semester_options_are_loaded_from_the_siakad_api(): void
    {
        $siakad = Mockery::mock(UnwApiSiakad::class);
        $siakad->shouldReceive('semester')
            ->once()
            ->andReturn([
                ['id' => 2, 'kode' => '', 'nama' => 'Semester Genap'],
                ['id' => 1, 'kode' => '', 'nama' => 'Semester Gasal'],
            ]);
        $this->app->instance(UnwApiSiakad::class, $siakad);

        $this->assertSame([
            2 => 'Semester Genap',
            1 => 'Semester Gasal',
        ], EdomPeriodForm::semesterOptions());
    }

    public function test_invalid_semester_rows_are_not_shown_as_options(): void
    {
        $siakad = Mockery::mock(UnwApiSiakad::class);
        $siakad->shouldReceive('semester')
            ->once()
            ->andReturn([
                ['id' => null, 'nama' => 'Tidak valid'],
                ['id' => 1, 'nama' => ''],
            ]);
        $this->app->instance(UnwApiSiakad::class, $siakad);

        $this->assertSame([
            1 => 'Semester 1',
        ], EdomPeriodForm::semesterOptions());
    }
}
