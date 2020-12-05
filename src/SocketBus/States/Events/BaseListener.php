<?php

namespace SocketBus\States\Events;

use SocketBus\SocketBus\Curl;
use Illuminate\Database\Eloquent\Model;
use SocketBus\States\DataYetModel;

class BaseListener {
    public $curl;

    public function __construct()
    {
        $this->curl = new Curl([
            'app_id' => config('broadcasting.connections.socketbus.app_id'),
            'secret' => config('broadcasting.connections.socketbus.secret')
        ]);
    }

    public function broadcastState(string $event, DataYetModel $model, array $data)
    {
        if (!$model->getShouldBroadcast()) {
            return false;
        }

        return $this->curl->stateEvent(
            $event, 
            get_class($model),
            $model->getAttribute($model->getKeyName()), 
            $data
        );
    }
}