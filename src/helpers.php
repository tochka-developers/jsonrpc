<?php

use Illuminate\Support\Facades\App;

if (!function_exists('config_path')) {
    /**
     * Get the configuration path.
     */
    function config_path(string $path = ''): string
    {
        return App::basePath() . '/config' . ($path ? '/' . $path : $path);
    }
}

if (!function_exists('is_lumen')) {
    /**
     * Check lumen framework
     */
    function is_lumen(): bool
    {
        return stripos(App::version(), "Lumen") !== false;
    }
}

if (!function_exists('getVersion')) {
    /**
     * Check framework version
     */
    function getVersion(): string
    {
        return preg_replace('/.*(([0-9]\.[0-9])[0-9]*).*/ui', '$2', App::version());
    }
}
