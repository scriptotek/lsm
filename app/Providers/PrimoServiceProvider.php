<?php

namespace App\Providers;

use BCLib\PrimoServices\PrimoServices;
use Illuminate\Support\ServiceProvider;

class PrimoServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(PrimoServices::class, function ($app) {
            return new PrimoServices(
                config('primo.host'),
                config('primo.institution')
            );
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [PrimoServices::class];
    }
}
