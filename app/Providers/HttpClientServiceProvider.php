<?php

namespace App\Providers;

use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Message\MessageFactory;
use Illuminate\Support\ServiceProvider;
use Http\Discovery\MessageFactoryDiscovery;

class HttpClientServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->app->bind(HttpClient::class, function () {
            return HttpClientDiscovery::find();
        });

        $this->app->bind(MessageFactory::class, function ($app) {
            return MessageFactoryDiscovery::find();
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
            HttpClient::class,
            MessageFactory::class,
        ];
    }
}
