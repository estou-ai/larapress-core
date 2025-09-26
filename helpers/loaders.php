<?php

function loadFiles($path): void
{
    $files = glob(__DIR__ . '/' . config('app.slug') . 'loaders.php/' .$path . '/*.php');

    foreach ($files as $file) {
        require_once $file;
    }
}

function configLoader(): void
{
    $path = BASE_PATH.'/config';

    $files = glob($path.'/*.php');

    global $configs;

    if (!function_exists('plugin_dir_url')){
        function plugin_dir_url(...$args): string
        {
            return '';
        }
    }

    foreach ($files as $file) {
        $filename = basename($file, '.php');
        $configs[$filename] = require $file;
    }
}

function loadResources(): void
{
    $path = BASE_PATH . '/App/Resources';

    if (!is_dir($path)) {
        return;
    }

    $files = scandir($path);

    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file === 'Dependencies') {
            continue;
        }

        $class = "App\\Resources" . '\\' . pathinfo($file, PATHINFO_FILENAME);

        if (class_exists($class) && method_exists($class, '__construct')) {
            new $class();
        }
    }
}

function loadShortCodes(): void
{
    $path = __DIR__ . '/../ShortCodes';
    if (!is_dir($path)) {
        return;
    }
    $files = scandir($path);

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $class = "App\\ShortCodes" . '\\' . pathinfo($file, PATHINFO_FILENAME);
        if (class_exists($class) && method_exists($class, '__construct')) {
            new $class();
        }
    }
}

