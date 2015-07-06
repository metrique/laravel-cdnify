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
        ]);
        
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
        $this->registerCDNify();
        $this->registerCommands();
    }

    /**
     * Register the cdnify singleton bindings.
     *
     * @return void
     */
    public function registerCDNify()
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