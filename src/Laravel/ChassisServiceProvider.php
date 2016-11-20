<?php

namespace Chassis\Laravel;

use Chassis\Bot\BotsManager;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Container\Container as Application;
use Laravel\Lumen\Application as LumenApplication;
use Illuminate\Foundation\Application as LaravelApplication;


class ChassisServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupConfig($this->app);
    }

    /**
     * Setup the config.
     *
     * @param \Illuminate\Contracts\Container\Container $app
     *
     * @return void
     */
    protected function setupConfig(Application $app)
    {
//        $source = __DIR__.'/config/chassis.php';

//        if ($app instanceof LaravelApplication && $app->runningInConsole()) {
        $this->publishes([
            __DIR__.'/config/chassis.php' => config_path('chassis.php')
        ], 'config');

        $this->publishes([
            __DIR__.'/migrations' => database_path('migrations')
        ], 'migrations');

//        } elseif ($app instanceof LumenApplication) {
//            $app->configure('chassis');
//        }

//        $this->mergeConfigFrom($source, 'chassis');

    }

    public function register() {
        $this->commands([
            \Chassis\Console\Commands\ChassisHandle::class,
            \Chassis\Console\Commands\TelegramMe::class,
            \Chassis\Console\Commands\ChassisFlush::class,
        ]);


        $this->app->singleton('chassis', function ($app) {
            $chassisConfig = (array)$app['config']['chassis'];
            $telegramConfig = (array)$app['config']['telegram'];

            return (new BotsManager($chassisConfig, $telegramConfig));
        });

        $this->app->alias('chassis', BotsManager::class);
    }
}
