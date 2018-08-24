<?php

namespace App\Providers;

use App\DocumentRepository;
use BCLib\PrimoServices\PrimoServices;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(PrimoServices::class, function ($app) {
            return new PrimoServices(
                config('app.primo.host'),
                config('app.primo.institution')
            );
        });

        $this->app->singleton(DocumentRepository::class, function ($app) {
            return new DocumentRepository(
                $app['alma']
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
        return [
            PrimoServices::class,
            DocumentRepository::class,
        ];
    }
}
