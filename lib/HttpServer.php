<?php

namespace Uccu\SwKoa;

use Swoole\Coroutine\Http\Server as CoServer;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Process\Pool;
use Uccu\SwKoa\Context;
use Uccu\SwKoa\MiddlewarePool;

class HttpServer
{

    public static function _execFunc(int $port, Pool $pool, int $workerId)
    {
        $host = '127.0.0.1';
        $server = new CoServer($host, $port, false, true);

        $server->handle('/', function (Request $request, Response $response) use ($pool, $workerId) {
            $middlewarePool = new MiddlewarePool;

            if (!defined("ROOT_PATH")) {
                define("ROOT_PATH", getcwd());
            }

            $path =  ROOT_PATH . '/Middleware.php';
            if (file_exists($path)) {
                $middlewares = require($path);
                foreach ($middlewares as $m) {
                    $middlewarePool->push(new $m);
                }
            }

            $ctx = new Context($request, $response, $pool, $workerId);
            Context::generateNextFunc($ctx, $middlewarePool)();
        });

        Server::$logger::info("worker start: " . $host . ":" . $port, 'server');
        $server->start();
    }
}
