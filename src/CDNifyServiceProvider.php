<?php

namespace Metrique\CDNify;

use Illuminate\Support\ServiceProvider;

class CDNifyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {   
        // Config
        $this->publishes([
            __DIR__.'/Resources/config/cdnify.php' => config_path('cdnify.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // $this->app->singleton(
        // );
    }
}