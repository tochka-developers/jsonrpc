<?php


namespace Tochka\JsonRpc\Installer;


use Tochka\JsonRpc\JsonRpcServiceProvider;

class Installer
{
    public static function run($event): void
    {
        (new JsonRpcServiceProvider())->boot();
    }
}