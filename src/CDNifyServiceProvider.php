<?php

namespace Metrique\CDNify;

use Illuminate\Support\ServiceProvider;
use Metrique\CDNify\Contracts\CDNifyRepositoryInterface;
use Metrique\CDNify\CDNifyRepository;

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

        // View composer
        view()->composer(
            '*',
            'Metrique\CDNify\CDNifyViewComposer'
        );
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(
            CDNifyRepositoryInterface::class,
            CDNifyRepository::class
        );
    }
}