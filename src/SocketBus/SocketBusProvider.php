<?php

namespace SocketBus;

use Illuminate\Support\ServiceProvider;
use Illuminate\Broadcasting\BroadcastManager;

class SocketBusProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->resolving(BroadcastManager::class, function($broadcastManager, $app){
            $broadcastManager->extend('socketbus', function($app, $settings){
                return new SocketBusLaravelDriver($settings);
            });
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
    }
}
