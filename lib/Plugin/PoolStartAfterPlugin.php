<?php

namespace Uccu\SwKoa\Plugin;

use Uccu\SwKoa\PoolManager;

interface PoolStartAfterPlugin extends Plugin
{
    function poolStartAfter(PoolManager $poolManager);
}
