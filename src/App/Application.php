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

    /**
     * Sessions are started on first use, not on every request.
     *
     * An unconditional session_start() sends a PHPSESSID cookie on every
     * page, which defeats full-page caching (Varnish, Cloudflare, WP
     * Super Cache) across the whole site. Admin requests are never cached,
     * so those start eagerly and existing admin code keeps working.
     */
    public function loadSession(): self
    {
        if (is_admin()) {
            session();
        }

        return $this;
    }

    public function loadConfig(): self
    {
        configLoader();
        return $this;
    }

    /**
     * Loads the plugin's text domain from config/app.php.
     *
     * At priority 1 on init, ahead of post types and meta, whose labels call
     * __() as they register: load the domain after them and WordPress warns
     * that translations were triggered too early and hands back the
     * untranslated strings.
     *
     * The domain is the app slug, and .mo files live in languages/ unless
     * app.languages_path says otherwise.
     */
    public function loadTranslations(): self
    {
        add_action('init', static function () {
            $domain = (string) config('app.slug');

            if ($domain === '') {
                return;
            }

            $relative = trim((string) config('app.languages_path', 'languages'), '/');

            // load_plugin_textdomain() resolves its path against the plugins
            // directory, so it needs the plugin folder's name — which for a
            // symlinked install is the link, not basename(BASE_PATH). Reading
            // the real directory would point WordPress at a path it cannot
            // see and translations would silently never load.
            $pluginDir = self::pluginDirName($domain);

            load_plugin_textdomain($domain, false, $pluginDir . '/' . $relative);
        }, 1);

        return $this;
    }
    public function loadAdmin(): self
    {
        if (!is_admin()) {
            return $this;
        }

        include_once(ABSPATH . 'wp-admin/includes/plugin.php');

        $this->registerErrorHandler();

        loadResources();

        return $this;
    }

    /**
     * Pretty error pages, for developers only.
     *
     * is_admin() is true for any request to wp-admin — including
     * admin-ajax.php, which unauthenticated visitors can reach — so it says
     * nothing about who is asking. Gating on it alone served full stack
     * traces, file paths and function arguments to anonymous users. Require
     * WP_DEBUG and a capability only a developer-grade account holds.
     */
    private function registerErrorHandler(): void
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        // The current user is not resolved this early in the load, so the
        // capability check waits for init rather than silently passing.
        add_action('init', function () {
            if (!current_user_can('manage_options')) {
                return;
            }

            $whoops = new \Whoops\Run;
            $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
            $whoops->register();
        });
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

    /**
     * The plugin's directory name under wp-content/plugins.
     *
     * Prefers a directory matching the slug, which is what `lp install`
     * symlinks, and falls back to the real path for a plugin copied in
     * under its own name.
     */
    private static function pluginDirName(string $slug): string
    {
        if (defined('WP_PLUGIN_DIR') && is_dir(WP_PLUGIN_DIR . '/' . $slug)) {
            return $slug;
        }

        return basename(BASE_PATH);
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