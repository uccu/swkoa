<?php

namespace Uccu\SwKoa;

use Swoole\Process;
use Swoole\Process\Pool;

class Server
{

    /**
     * @var Config $config
     */
    static private $config;

    /**
     * @var Logger $logger
     */
    static public $logger;

    static function setConfig($conf)
    {
        self::$config = $conf;
    }

    static function setLog($logger)
    {
        self::$logger = $logger;
    }

    static function init()
    {
        $port = self::$config::get('app.PORT');
        if (!$port) {
            $port = 9501;
        }

        $port = intval($port);

        $workerNum = self::$config::get('app.WORKER_NUM');
        if (!$workerNum) {
            $workerNum = swoole_cpu_num();
        }

        $workerNum = intval($workerNum);

        $funcs = [];

        array_push($funcs, [Server::$logger, '_execFunc']);

        for ($i = 0; $i++ < $workerNum;) {
            $funcs[] = function (Pool $pool, int $workerId) use ($port) {
                Server::$logger::setPool($pool, $workerId);
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
}
