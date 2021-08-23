<?php

namespace Uccu\SwKoa;

class MiddlewarePool
{
    private $middleware = [];

    function push(Middleware $m)
    {
        array_push($this->middleware, $m);
    }

    function pop(): Middleware
    {
        return array_pop($this->middleware);
    }
}
