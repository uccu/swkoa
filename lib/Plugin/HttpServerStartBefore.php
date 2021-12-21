<?php

namespace Uccu\SwKoa\Plugin;

use Uccu\SwKoa\HttpServer;

interface HttpServerStartBefore extends Plugin
{
    function httpServerStartBefore(HttpServer $httpServer);
}
