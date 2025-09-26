<?php
namespace Larapress\App;

use As247\WpEloquent\Application as WpApplication;

class Application
{

    public static function make(): Application
    {
        require_once __DIR__.'/../../autoload.php';

        return new self();
    }

    public function loadDatabase(): self
    {
        WpApplication::bootWp();

        return $this;
    }

    public function loadSession(): self
    {
        session_start();
        return $this;

    }

    public function loadConfig(): self
    {
        configLoader();
        return $this;
    }
    public function loadAdmin(): self
    {
        if (!is_admin()) {
            return $this;
        }

        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        $whoops = new \Whoops\Run;
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
        $whoops->register();
        loadResources();

        return $this;
    }

    public function loadResources(): self
    {
        loadFiles('helpers');

        loadShortCodes();

        return $this;
    }
}