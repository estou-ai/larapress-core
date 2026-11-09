<?php

namespace Larapress\Database;

/**
 * One custom table, created and migrated through dbDelta.
 *
 * Subclasses declare the table suffix, the column definition and a version.
 * Bump VERSION whenever the definition changes: install() runs on activation
 * and ensure() runs on admin_init, which is what catches plugin updates
 * (activation hooks never fire on update).
 */
abstract class BaseSchema
{
    /** Table name without the WordPress prefix. */
    abstract public function name(): string;

    /** Columns and keys, as the body of a CREATE TABLE statement. */
    abstract public function definition(): string;

    /** Bump when definition() changes so existing installs migrate. */
    public function version(): string
    {
        return '1';
    }

    public function table(): string
    {
        global $wpdb;

        return $wpdb->prefix . $this->name();
    }

    public function versionOption(): string
    {
        return 'larapress_schema_' . $this->name();
    }

    public function ensure(): void
    {
        if (get_option($this->versionOption()) === $this->version()) {
            return;
        }

        $this->install();
    }

    public function install(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta(sprintf(
            'CREATE TABLE %s (%s) %s;',
            $this->table(),
            trim($this->definition()),
            $wpdb->get_charset_collate()
        ));

        update_option($this->versionOption(), $this->version());
    }
}
