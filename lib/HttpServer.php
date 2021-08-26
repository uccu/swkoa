<?php

namespace Uccu\SwKoa;

use Swoole\Coroutine\Http\Server as CoServer;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Uccu\SwKoa\Context;
use Uccu\SwKoa\MiddlewarePool;

class HttpServer
{

    public static function _execFunc(int $port)
    {
        $host = '127.0.0.1';
        $server = new CoServer($host, $port);

        $server->handle('/', function (Request $request, Response $response) {
            $pool = new MiddlewarePool;

            if (!defined("ROOT_PATH")) {
                define("ROOT_PATH", getcwd());
            }

            $path =  ROOT_PATH . '/Middleware.php';
            if (file_exists($path)) {
                $middlewares = require($path);
                foreach ($middlewares as $m) {
                    $pool->push(new $m);
                }
            }

            $ctx = new Context($request, $response, $workerId);

            Context::generateNextFunc($ctx, $pool)();
        });

        Server::$logger::info("worker start: " . $host . ":" . $port, 'server');
        $server->start();
    }
}
