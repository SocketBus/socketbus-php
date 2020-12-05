<?php
namespace ValterLorran\SocketBus\States;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Database\Query\Builder;

class DataYetRouter {
    protected $states = [];

    public function state(string $channel, callable $callback, Builder $builder = null) {
        $result = $callback(null);
        $this->states[$channel] = $callback;

        $tableName = $result->getQuery()->from;
        $orderFormated = join(", ", array_map(function($order) use ($tableName){
            return "FIND_IN_SET(`{$order['column']}`, (    
                SELECT GROUP_CONCAT(`{$order['column']}`
                ORDER BY `{$order['column']}` {$order['direction']}) 
                FROM `{$tableName}`)
            ) AS dt_rank_{$order['column']}";
        }, $result->getQuery()->orders));

        $result = $result->addSelect('*')
        ->addSelect(\DB::raw($orderFormated));

        dd($result->get());
    }
}