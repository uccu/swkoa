<?php

namespace Uccu\SwKoa;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Process\Pool;

class Context
{

    public  $request;
    public  $response;
    public  $workerId;
    public  $pool;

    function __construct(Request $request, Response $response, Pool $pool, int $workerId)
    {
        $this->request =  $request;
        $this->response =  $response;
        $this->pool =  $pool;
        $this->workerId =  $workerId;
    }


    static public function generateNextFunc(Context $ctx, MiddlewarePool $pool)
    {
        return function (...$p) use ($ctx, $pool) {
            $cla = $pool->pop();
            if ($cla === null) {
                return function () {
                };
            }
            return $cla->handle($ctx, Context::generateNextFunc($ctx, $pool), ...$p);
        };
    }
}
