<?php

if (!function_exists('config')){
    function config($key, $default = null)
    {
        global $configs;
        return array_get($configs, $key, $default);
    }
}