<?php

namespace SocketBus\States\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;

class CreatedEvent
{
    use SerializesModels;

    public $model;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }
}
