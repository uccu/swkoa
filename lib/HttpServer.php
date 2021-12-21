<?php

namespace Uccu\SwKoa;

use Swoole\Coroutine\Http\Server as CoServer;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Process\Pool;
use Uccu\SwKoa\Context;
use Uccu\SwKoa\MiddlewarePool;
use Psr\Log\LoggerInterface;
use Uccu\SwKoa\Plugin\PluginLoader;

class HttpServer
{


    /**
     * @var PluginLoader
     */
    public $pluginLoader;

    /**
     * @var Pool
     */
    public $pool;

    /**
     * @var int
     */
    public $workerId;

    /**
     * The logger instance.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
        if (method_exists($logger, 'setConfig')) {
            call_user_func([$logger, 'setConfig'], [
                'pool' => $this->pool,
                'workerId' => $this->workerId,
                'tag' => 'http',
                'importFile' => false
            ]);
        }
    }

    public function __construct(PluginLoader $pluginLoader)
    {
        $this->pluginLoader = $pluginLoader;
    }

    public function poolStartBefore(PoolManager $poolManager)
    {
        $poolManager->add(function (Pool $pool, int $workerId) {

            $this->pool = $pool;
            $this->workerId = $workerId;

            $port = $this->config->get('app.PORT');
            if (!$port) {
                $port = 9501;
            }

            $port = intval($port);

            $workerNum = $this->config->get('app.WORKER_NUM');
            if (!$workerNum) {
                $workerNum = swoole_cpu_num();
            }

            $workerNum = intval($workerNum);

            $host = $this->config->get('app.HOST');
            if (!$host) {
                $host = "0.0.0.0";
            }

            $this->pluginLoader->httpServerStartBefore($this);

            $server = new CoServer($host, $port, false, true);

            $server->handle('/', function (Request $request, Response $response) use ($pool, $workerId) {
                $middlewarePool = new MiddlewarePool;

                $path =  getcwd() . '/Middleware.php';
                if (file_exists($path)) {
                    $middlewares = require($path);
                    foreach ($middlewares as $m) {
                        $middlewarePool->push(new $m);
                    }
                }

                $ctx = new Context($request, $response, $pool, $workerId);
                Context::generateNextFunc($ctx, $middlewarePool)();
            });

            $this->logger->info("worker start: {host}:{port}", ['host' => $host, 'port' => $port]);
            $server->start();
        });
    }
}
