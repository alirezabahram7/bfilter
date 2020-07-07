<?php


namespace Behamin\Bfilters;


use Illuminate\Support\ServiceProvider;

class BfilterServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
               \Behamin\Bfilters\Console\Filter::class
            ]);
        }
    }
}
