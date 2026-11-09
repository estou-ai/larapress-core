<?php
namespace Larapress\App;

use As247\WpEloquent\Application as WpApplication;
use Larapress\Cron\BaseCron;
use Larapress\Database\BaseSchema;

class Application
{
    /** @var BaseSchema[] */
    private array $schemas = [];

    /** @var BaseCron[] */
    private array $crons = [];

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

    /**
     * Post types must register on the front end as well, so this deliberately
     * sits outside loadAdmin()'s is_admin() gate.
     */
    public function loadPostTypes(): self
    {
        loadPostTypes();

        return $this;
    }

    /** Custom tables in App/Database, migrated on activation and on update. */
    public function loadSchemas(): self
    {
        $this->schemas = loadSchemas();

        return $this;
    }

    /** Recurring jobs in App/Cron, scheduled on activation and on update. */
    public function loadCrons(): self
    {
        $this->crons = loadCrons();

        return $this;
    }

    /**
     * Wires activation and deactivation for what was loaded above: tables get
     * created, crons scheduled, and crons cleared again on deactivation.
     *
     * Call it after loadSchemas()/loadCrons(), which collect what it needs.
     */
    public function loadLifecycle(string $pluginFile): self
    {
        $schemas = $this->schemas;
        $crons = $this->crons;

        register_activation_hook($pluginFile, function () use ($schemas, $crons) {
            foreach ($schemas as $schema) {
                $schema->install();
            }

            foreach ($crons as $cron) {
                $cron->schedule();
            }
        });

        register_deactivation_hook($pluginFile, function () use ($crons) {
            foreach ($crons as $cron) {
                $cron->unschedule();
            }
        });

        return $this;
    }

    /** @return BaseSchema[] */
    public function schemas(): array
    {
        return $this->schemas;
    }

    /** @return BaseCron[] */
    public function crons(): array
    {
        return $this->crons;
    }
}