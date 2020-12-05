<?php

namespace ValterLorran\SocketBus\States\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;

class DeletedListener extends BaseListener
{
    /**
     * Handle the event.
     *
     * @param  \App\Events\DeletedEvent  $event
     * @return void
     */
    public function handle(DeletedEvent $event)
    {
        return $this->broadcastState('deleted', $event->model, $event->model->toArray());
    }
}
