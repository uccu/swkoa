<?php

namespace Uccu\SwKoa;

use Env\Env;
use Swoole\Coroutine\Context as CoroutineContext;
use Swoole\Process\Pool;
use Swoole\Coroutine\Http\Server as CoServer;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Uccu\SwKoa\Context;
use Uccu\SwKoa\MiddlewarePool;
use WsKoaException;

class Server
{

    /**
     * @var Config $config
     */
    static private $config;

    static function setConfig(Config $conf)
    {
        self::$config = $conf;
    }

    static function init()
    {
        $ports = self::$config->get('PORTS');
        if (!$ports) {
            throw new WsKoaException("pls specify the port!");
        }

        $portArr = explode(',', $ports);
        $cores = count($portArr);
        if ($cores === 0) {
            throw new WsKoaException("pls specify the port!");
        }


        $pool = new Pool($cores, SWOOLE_IPC_UNIXSOCK, 0, true);


        $pool->on('WorkerStart', function (Pool $pool, int $workerId) use ($portArr) {

            $host = '127.0.0.1';
            $port = $portArr[$workerId];
            $server = new CoServer($host, $port);

            $server->handle('/', function (Request $request, Response $response) use ($workerId) {
                $pool = new MiddlewarePool;

                $path = ROOT_PATH . '/Middleware.php';
                if (file_exists($path)) {
                    $middlewares = require($path);
                    foreach ($middlewares as $m) {
                        $pool->push(new $m);
                    }
                }

                $ctx = new Context($request, $response, $workerId);

                Context::generateNextFunc($ctx, $pool)();
            });

            echo "开启服务:" . $host . ":" . $port . ", workerId:" . $workerId . "\n";
            $server->start();
        });
        $pool->on('WorkerStop', function (Pool $pool, $workerId) {
            echo ("[Worker #{$workerId}] WorkerStop\n");
        });
        $pool->start();
    }
}
