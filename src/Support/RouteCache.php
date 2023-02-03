<?php

namespace Tochka\JsonRpc\Support;

use Tochka\Cache\ArrayFileCache;
use Tochka\JsonRpc\Contracts\RouteCacheInterface;

class RouteCache extends ArrayFileCache implements RouteCacheInterface
{
}
