<?php

namespace Uccu\SwKoa;

interface Middleware
{
    public function handle(Context $ctx, callable $next);
}
