<?php

namespace App\Providers;

use App\Services\Edom\EdomResponseMetadata;
use App\Services\Edom\EdomResultAggregator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(EdomResponseMetadata::class);
        $this->app->scoped(EdomResultAggregator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
