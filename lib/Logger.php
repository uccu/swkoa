<?php

namespace Uccu\SwKoa;

use Psr\Log\LoggerInterface;

interface Logger extends LoggerInterface
{
    public function setConfig(array $config);
    public static function start(array $config);
}
