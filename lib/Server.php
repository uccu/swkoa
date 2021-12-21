<?php

namespace Uccu\SwKoa;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Swoole\Process;
use Swoole\Process\Pool;
use Uccu\SwKoa\Plugin\PluginLoader;

class Server implements LoggerAwareInterface
{

    /**
     * The Pool instance.
     *
     * @var Pool
     */
    protected $pool;

    /**
     * @var int
     */
    protected $workerId;

    /**
     * @var int
     */
    protected $masterWorkerId;


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
                'masterWorkerId' => $this->masterWorkerId,
                'tag' => 'master',
                'importFile' => false
            ]);
        }
    }


    /**
     * @var Config $config
     */
    private $config;

    public function setConfig($conf)
    {
        $this->config = $conf;
    }

    public function init()
    {
        $pluginLoader = new PluginLoader;
        $manager = new PoolManager;

        $pluginLoader->load();

        $pluginLoader->poolStartBefore($manager);
        $manager->start();
        $pluginLoader->poolStartAfter($manager);
    }
}

function init()
{


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

    $funcs = [];

    array_push($funcs, [Server::$logger, 'start']);

    for ($i = 0; $i++ < $workerNum;) {
        $funcs[] = function (Pool $pool, int $workerId) use ($port) {

            $server = new Server;
            $server->setLogger(new Lo);
            HttpServer::_execFunc($port, $pool, $workerId);
        };
    }


    $pool = new Pool(count($funcs), SWOOLE_IPC_UNIXSOCK, 0, true);
    $pool->on('WorkerStart', function (Pool $pool, int $workerId) use ($funcs) {
        Process::signal(SIGTERM, function () {
            Server::$logger::info("worker SIGTERM", 'server');
        });
        call_user_func($funcs[$workerId], $pool,  $workerId);
    });
    $pool->on('WorkerStop', function () {
        Server::$logger::info("worker stop", 'server');
    });
    $pool->start();
}
