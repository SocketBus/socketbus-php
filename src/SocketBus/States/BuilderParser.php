<?php

namespace ValterLorran\SocketBus\States;

use Illuminate\Database\Eloquent\Builder;

class BuilderParser {
    private $builder;

    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }

    public function parse()
    {
        $parsed = [
            'from' => $this->builder->getQuery()->from,
            'wheres' => $this->wheres($this->builder->getQuery()->wheres),
            'orders' => $this->builder->getQuery()->orders,
            'query_type' => $this->builder->getModel()->getQueryType()
            // 'limit' => $this->builder->getQuery()->limit,
            // 'offset' => $this->builder->getQuery()->offset
        ];

        $hash = md5(json_encode($parsed));
        $parsed['hash'] = $hash;

        return $parsed;
    }

    public function wheres(array $wheres)
    {
        $formattedWheres = [];
        foreach($wheres as $where) {
            $formatedWhere = $where;
            if (isset($where['query'])) {
                $formatedWhere['sub'] = $this->wheres($where['query']->wheres);
                unset($formatedWhere['query']);
            }

            $formattedWheres[] = $formatedWhere;
        }

        return $formattedWheres;
    }
}