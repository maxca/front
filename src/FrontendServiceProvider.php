<?php
namespace Samark\Front;

use Illuminate\Support\ServiceProvider;
use Samark\Front\Command\CopyEnvCommand;

class FrontEndServiceProvider extends ServiceProvider
{
    /**
     * set command list
     * @var array
     */
    protected $commands = [
        CopyEnvCommand::class,
    ];

    /**
     * register servicde
     * @return [type] [description]
     */
    public function register()
    {
        $this->publishes([
            __DIR__ . '/config/front.php' => config_path('front.php'),
        ]);

        $this->mergeConfigFrom(
            __DIR__ . '/config/front.php', 'front'
        );
    }

    /**
     * booting application
     * @return [type] [description]
     */
    public function boot()
    {
        # $this->loadMigrationsFrom(__DIR__ . '/../database');

        # add command
        $this->commands($this->commands);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['samark.front'];
    }

}
