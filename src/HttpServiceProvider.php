<?php

namespace Febalist\LaravelHttp;

use Illuminate\Support\ServiceProvider;

class HttpServiceProvider extends ServiceProvider
{
    public static $abstract = 'http';

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config.php' => config_path('http.php'),
        ]);
        $this->app->singleton(static::$abstract, function ($app) {
            return new Http();
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
    }
}
