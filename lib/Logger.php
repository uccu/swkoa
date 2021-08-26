<?php

namespace Uccu\SwKoa;

use Swoole\Process\Pool;

interface Logger
{
    public static function info($logInfo, string $tag = '', int $level = 0);
    public static function setPool(Pool $pool, int $workerId);
    public static function _execFunc(Pool $pool, int $workerId);
}
