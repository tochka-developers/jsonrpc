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
