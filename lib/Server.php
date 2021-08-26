<?php

namespace Uccu\SwKoa;

use Swoole\Process;
use Swoole\Process\Pool;
use WsKoaException;

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

    static function setConfig(Config $conf)
    {
        self::$config = $conf;
    }

    static function setLog(Logger $logger)
    {
        self::$logger = $logger;
    }

    static function init()
    {
        $ports = self::$config->get('PORTS');
        if (!$ports) {
            throw new WsKoaException("pls specify the port!");
        }

        $portArr = array_map(function ($p) {
            return intval($p);
        }, explode(',', $ports));

        if (count($portArr) === 0) {
            throw new WsKoaException("pls specify the port!");
        }

        $funcs = [];

        array_push($funcs, [Server::$logger, '_execFunc']);

        while ($p = array_shift($portArr)) {
            $funcs[] = function (Pool $pool, int $workerId) use ($p) {
                Server::$logger::setPool($pool, $workerId);
                HttpServer::_execFunc($p);
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
