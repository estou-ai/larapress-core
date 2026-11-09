<?php

function loadFiles($path): void
{
    $files = glob(BASE_PATH . '/' . $path . '/*.php');

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

/**
 * Instantiates every class in App/<dir>, and returns the instances so callers
 * can keep working with them (scheduling crons, migrating schemas).
 *
 * @return object[]
 */
function loadClassesIn(string $dir): array
{
    $path = BASE_PATH . '/App/' . $dir;

    if (!is_dir($path)) {
        return [];
    }

    $instances = [];

    foreach (scandir($path) as $file) {
        if ($file === '.' || $file === '..' || $file === 'Dependencies') {
            continue;
        }

        if (!str_ends_with($file, '.php')) {
            continue;
        }

        $class = 'App\\' . str_replace('/', '\\', $dir) . '\\' . pathinfo($file, PATHINFO_FILENAME);

        if (!class_exists($class)) {
            continue;
        }

        $reflection = new ReflectionClass($class);

        if ($reflection->isAbstract() || !$reflection->isInstantiable()) {
            continue;
        }

        // Only no-argument constructors can be auto-instantiated; anything
        // needing dependencies is the plugin's own job to wire up.
        $constructor = $reflection->getConstructor();

        if ($constructor && $constructor->getNumberOfRequiredParameters() > 0) {
            continue;
        }

        $instances[] = new $class();
    }

    return $instances;
}

function loadResources(): void
{
    loadClassesIn('Resources');
}

function loadShortCodes(): void
{
    loadClassesIn('ShortCodes');
}

/**
 * Post types register on `init`, which runs on the front end too, so this is
 * called outside the is_admin() gate that wraps loadAdmin().
 */
function loadPostTypes(): void
{
    loadClassesIn('PostTypes');
}

/** @return Larapress\Cron\BaseCron[] */
function loadCrons(): array
{
    $crons = loadClassesIn('Cron');

    // Activation hooks never fire on plugin update, so (re)schedule here too.
    add_action('admin_init', function () use ($crons) {
        foreach ($crons as $cron) {
            $cron->schedule();
        }
    });

    return $crons;
}

/** @return Larapress\Database\BaseSchema[] */
function loadSchemas(): array
{
    $schemas = loadClassesIn('Database');

    add_action('admin_init', function () use ($schemas) {
        foreach ($schemas as $schema) {
            $schema->ensure();
        }
    });

    return $schemas;
}
