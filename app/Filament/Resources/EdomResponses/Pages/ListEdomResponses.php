<?php

namespace App\Filament\Resources\EdomResponses\Pages;

use App\Filament\Resources\EdomResponses\EdomResponseResource;
use App\Services\Edom\EdomResultAggregator;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;

class ListEdomResponses extends ListRecords
{
    protected static string $resource = EdomResponseResource::class;

    protected string $view = 'filament.resources.edom-responses.pages.list-edom-responses';

    /**
     * @return Collection<string, Collection<int, array<string, mixed>>>
     */
    public function getGroupedResults(): Collection
    {
        return app(EdomResultAggregator::class)->groupedSummaries();
    }
}
