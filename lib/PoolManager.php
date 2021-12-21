<?php

namespace Uccu\SwKoa;

use Swoole\Constant;
use Swoole\Process\Manager;
use Swoole\Process\Pool;

class PoolManager extends Manager
{

    public function __construct(int $msgQueueKey = 0)
    {
        $this->setIPCType(SWOOLE_IPC_UNIXSOCK)->setMsgQueueKey($msgQueueKey);
    }

    public function start(): void
    {
        $this->pool = new Pool(count($this->startFuncMap), $this->ipcType, $this->msgQueueKey, true);

        $this->pool->on(Constant::EVENT_WORKER_START, function (Pool $pool, int $workerId) {
            [$func] = $this->startFuncMap[$workerId];
            $func($pool, $workerId);
        });

        $this->pool->start();
    }
}
