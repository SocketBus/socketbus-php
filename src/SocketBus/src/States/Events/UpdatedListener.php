<?php

namespace ValterLorran\SocketBus\States\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;

class UpdatedListener extends BaseListener
{
    /**
     * Handle the event.
     *
     * @param  \App\Events\UpdatedEvent  $event
     * @return void
     */
    public function handle(UpdatedEvent $event)
    {
        return $this->broadcastState('updated', $event->model, $event->model->getChanges());
    }
}
