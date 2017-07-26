<?php

if ( ! function_exists('config_path'))
{
    /**
     * Get the configuration path.
     *
     * @param  string $path
     * @return string
     */
    function config_path($path = '')
    {
        return app()->basePath() . '/config' . ($path ? '/' . $path : $path);
    }
}

if ( ! function_exists('is_lumen'))
{
    /**
     * Check lumen framework
     *
     * @return boolean
     */
    function is_lumen()
    {
        return (bool) preg_match('/Lumen/iu', app()->version());
    }
}