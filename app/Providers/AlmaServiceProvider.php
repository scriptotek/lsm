<?php

namespace App\Providers;

use Guzzle\Http\Client as HttpClient;
use Illuminate\Support\ServiceProvider;

class AlmaServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('BCLib\PrimoServices\Availability\AlmaClient', function ($app) {
            return new \BCLib\PrimoServices\Availability\AlmaClient(
                new HttpClient(),
                config('app.alma.host'),
                config('app.alma.institution')
            );
        });
    }
}
