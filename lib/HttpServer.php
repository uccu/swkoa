<?php

namespace Uccu\SwKoa;

use Swoole\Coroutine\Http\Server as CoServer;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Process\Pool;
use Uccu\SwKoa\Context;
use Uccu\SwKoa\MiddlewarePool;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use Swoole\Process\Manager;
use Uccu\SwKoaPlugin\Plugin\PluginLoader;
use Uccu\SwKoaPlugin\Plugin\PoolStartBeforePlugin;

class HttpServer implements LoggerAwareInterface, PoolStartBeforePlugin
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
    }

    public function __construct(PluginLoader $pluginLoader)
    {
        $this->pluginLoader = $pluginLoader;
    }

    public function poolStartBefore(Manager $manager)
    {

        $host = $this->config->get('app.HOST');
        if (!$host) {
            $host = "0.0.0.0";
        }

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


        $manager->addBatch($workerNum, function (Pool $pool, int $workerId) use ($host, $port) {

            $this->pool = $pool;
            $this->workerId = $workerId;

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
