<?php

namespace SocketBus\States\Events;

use Illuminate\Database\Eloquent\Model;

class CreatedListener extends BaseListener
{
    /**
     * Handle the event.
     *
     * @param  \App\Events\CreatedEvent  $event
     * @return void
     */
    public function handle(CreatedEvent $event)
    {
        return $this->broadcastState('created', $event->model, $event->model->toArray());
    }
}
