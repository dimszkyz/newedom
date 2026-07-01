<?php

namespace App\Filament\Pages;

use App\Services\Edom\EdomResultAggregator;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class HasilEdom extends Page
{
    protected static ?string $navigationLabel = 'Hasil EDOM';

    protected static string|\UnitEnum|null $navigationGroup = 'EDOM';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 19;

    protected string $view = 'filament.pages.hasil-edom';

    /**
     * @return Collection<string, Collection<int, array<string, mixed>>>
     */
    public function getGroupedResults(): Collection
    {
        return app(EdomResultAggregator::class)->groupedSummaries();
    }
}
