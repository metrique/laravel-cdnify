<?php

namespace Metrique\CDNify;

use Illuminate\Support\ServiceProvider;
use Metrique\CDNify\Contracts\CDNifyRepositoryInterface;
use Metrique\CDNify\CDNifyRepository;
use Metrique\CDNify\Commands\CDNifyCommand;

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
        ], 'cdnify-config');
        
        // Commands
        $this->commands('command.metrique.cdnify');

        // View composer
        view()->composer('*', 'Metrique\CDNify\CDNifyViewComposer');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCDNifyRepository();
        $this->registerCommands();
    }

    /**
     * Register the CDNifyRepository singleton binding.
     *
     * @return void
     */
    public function registerCDNifyRepository()
    {
        $this->app->singleton(
            CDNifyRepositoryInterface::class,
            CDNifyRepository::class
        );
    }

    /**
     * Register the artisan commands.
     *
     * @return void
     */
    private function registerCommands()
    {
        $this->app->bindShared('command.metrique.cdnify', function ($app) {
            return new CDNifyCommand();
        });
    }
}