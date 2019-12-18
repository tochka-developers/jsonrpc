<?php

if (!function_exists('config_path')) {
    /**
     * Get the configuration path.
     *
     * @param string $path
     * @return string
     */
    function config_path($path = '')
    {
        return app()->basePath() . '/config' . ($path ? '/' . $path : $path);
    }
}

if (!function_exists('is_lumen')) {
    /**
     * Check lumen framework
     *
     * @return boolean
     */
    function is_lumen()
    {
        return (bool)preg_match('/Lumen/iu', app()->version());
    }
}

if (!function_exists('getVersion')) {
    /**
     * Check framework version
     *
     * @return boolean
     */
    function getVersion()
    {
        return preg_replace('/.*(([0-9]\.[0-9])[0-9]*).*/ui', '$2', app()->version());
    }
}
