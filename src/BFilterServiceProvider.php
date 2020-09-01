<?php
namespace BFilters;

use Illuminate\Support\ServiceProvider;

class BFilterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
               '\BFilters\Console\Filter'
            ]);
        }
    }
}
