<?php

namespace Uccu\SwKoa;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use Uccu\SwKoaPlugin\Plugin\PluginLoader as PluginPluginLoader;

class App implements LoggerAwareInterface
{

    /**
     * @var Config $config
     */
    public static $config;

    /**
     * @var Config $config
     */
    public function setConfig($config)
    {
        App::$config = $config;
    }

    /**
     * @var LoggerInterface $logger
     */
    public static $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger($logger)
    {
        App::$logger = $logger;
    }

    public function start()
    {
        $pluginLoader = new PluginPluginLoader;
        $manager = new PoolManager;

        $pluginLoader->load();

        $pluginLoader->poolStartBefore($manager);
        $manager->start();
        $pluginLoader->poolStartAfter($manager);
    }
}
