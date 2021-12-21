<?php

namespace Uccu\SwKoa;

use Uccu\SwKoa\Plugin\PluginLoader;

class App
{

    public function start()
    {
        $pluginLoader = new PluginLoader;
        $manager = new PoolManager;

        $pluginLoader->load();

        $pluginLoader->poolStartBefore($manager);
        $manager->start();
        $pluginLoader->poolStartAfter($manager);
    }
    
}
