<?php

namespace SocketBus;

use Illuminate\Support\ServiceProvider;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\Facades\Event;
use SocketBus\States\Events\CreatedEvent;
use SocketBus\States\Events\CreatedListener;
use SocketBus\States\DataYetRouter;
use Illuminate\Database\Eloquent\Relations\Relation;

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

        $macroWatchMany = function(){
            $this->getRelated()->watchMany();
            return $this;
        };

        $macroWatchOne = function(){
            $this->getRelated()->watchOne();
            return $this;
        };

        Relation::macro('watchMany', $macroWatchMany);
        Relation::macro('watchOne', $macroWatchOne);

        $this->app->bind('dataYetRouter', function(){
            return new DataYetRouter;
        });

        Event::listen(CreatedEvent::class, CreatedListener::class);
        Event::listen(UpdatedEvent::class, UpdatedListener::class);
        Event::listen(DeletedEvent::class, DeletedListener::class);
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
