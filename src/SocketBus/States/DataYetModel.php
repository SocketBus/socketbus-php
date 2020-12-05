<?php

namespace SocketBus\States;
use Illuminate\Database\Eloquent\Model;
use SocketBus\States\Events\ { CreatedEvent, DeletedEvent, UpdatedEvent};

class DataYetModel extends Model {
    protected $dispatchesEvents = [
        'created' => CreatedEvent::class,
        'deleted' => DeletedEvent::class,
        'updated' => UpdatedEvent::class,
    ];

    protected $shouldBroadcast = true;


    protected $queryType = 'single';

    public function getShouldBroadcast()
    {
        return $this->shouldBroadcast;
    }

    public function shouldNotBroadcast()
    {
        $this->shouldBroadcast = false;
        return $this;
    }

    public function shouldBroadcast()
    {
        $this->shouldBroadcast = true;
        return $this;
    }

    public function watchOne()
    {
        $this->queryType = 'single';
        return $this;
    }

    public function watchMany()
    {
        $this->queryType = 'multiple';
        return $this;
    }

    public function getQueryType()
    {
        return $this->queryType;
    }
}