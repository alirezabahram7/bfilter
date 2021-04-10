<?php

namespace BFilters;

use Illuminate\Support\ServiceProvider;
use BFilters\Console\Filter;

class BFilterServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Filter::class
            ]);
        }
    }

    public function register()
    {
        $this->app->bind('MakeFilter', function (){
            return new MakeFilter();
        });
    }
}
