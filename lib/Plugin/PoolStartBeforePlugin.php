<?php

namespace Uccu\SwKoa\Plugin;

use Uccu\SwKoa\PoolManager;

interface PoolStartBeforePlugin extends Plugin
{
    function poolStartBefore(PoolManager $poolManager);
}
