<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class PrimoServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('BCLib\PrimoServices\PrimoServices', function ($app) {
            return new \BCLib\PrimoServices\PrimoServices(
                config('app.primo.host'),
                config('app.primo.institution')
            );
        });
    }
}
