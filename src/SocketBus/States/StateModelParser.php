<?php

namespace SocketBus\States;

use Illuminate\Database\Eloquent\Builder;

class StateModelParser {
    private $builder;

    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }

    public function getRows()
    {
        return $this->builder->get();
    }

    public function parse(): array {
        return (new BuilderParser($this->builder))->parse();
    }
}
