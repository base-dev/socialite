<?php

namespace Laravel\Socialite;

use Illuminate\Support\ServiceProvider;

use Laravel\Socialite\Contracts\Factory as FactoryContract;

class SocialiteServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(FactoryContract::class,
            function ($app) {
                return new SocialiteManager($app);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [FactoryContract::class];
    }
}
