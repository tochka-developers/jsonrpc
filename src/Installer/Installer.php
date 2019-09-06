<?php


namespace Tochka\JsonRpc\Installer;


class Installer
{
    public static function run($event): void
    {
        copy(__DIR__ . '/../config/jsonrpc.php', config_path('jsonrpc.php'));
    }
}