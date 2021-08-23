<?php

namespace Uccu\SwKoa;

use Swoole\Http\Request;
use Swoole\Http\Response;

class Context
{

    public  $request;
    public  $response;
    public  $workerId;

    function __construct(Request $request, Response $response, int $workerId)
    {
        $this->request =  $request;
        $this->response =  $response;
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
