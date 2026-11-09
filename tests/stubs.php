<?php

/**
 * The slice of WordPress the pure-logic tests touch, plus small fixtures.
 * Nothing here talks to a database or a real WordPress install.
 */

define('BASE_PATH', __DIR__ . '/fixtures');
define('HOUR_IN_SECONDS', 3600);
define('DAY_IN_SECONDS', 86400);

if (!is_dir(BASE_PATH)) {
    mkdir(BASE_PATH, 0777, true);
}

$GLOBALS['configs'] = [];
$GLOBALS['wp_actions_registered'] = [];

function esc_html($text): string
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

function esc_attr($text): string
{
    return esc_html($text);
}

function esc_textarea($text): string
{
    return esc_html($text);
}

function esc_url($url): string
{
    return esc_html($url);
}

function esc_url_raw($url): string
{
    return (string) $url;
}

function esc_html__($text, $domain = null): string
{
    return esc_html($text);
}

function __($text, $domain = null): string
{
    return (string) $text;
}

function _x($text, $context, $domain = null): string
{
    return (string) $text;
}

function _n($single, $plural, $number, $domain = null): string
{
    return $number === 1 ? $single : $plural;
}

function number_format_i18n($number): string
{
    return (string) $number;
}

function add_action($hook, $callback, $priority = 10, $args = 1): void
{
    $GLOBALS['wp_actions_registered'][] = $hook;
}

function add_filter($hook, $callback, $priority = 10, $args = 1): void
{
    $GLOBALS['wp_actions_registered'][] = $hook;
}

function is_admin(): bool
{
    return false;
}

function get_option($name, $default = false)
{
    return $GLOBALS['wp_options'][$name] ?? $default;
}

function update_option($name, $value): bool
{
    $GLOBALS['wp_options'][$name] = $value;
    return true;
}

function current_user_can($capability): bool
{
    return true;
}

function wp_next_scheduled($hook)
{
    return false;
}

function wp_schedule_event($timestamp, $recurrence, $hook): bool
{
    return true;
}

function wp_clear_scheduled_hook($hook): void
{
}

function register_post_meta($type, $key, $args): bool
{
    return true;
}

function plugin_dir_url(...$args): string
{
    return '';
}

/** Minimal $wpdb: only the prefix and charset the schema base reads. */
class TestWpdb
{
    public string $prefix = 'wp_';

    public function get_charset_collate(): string
    {
        return 'DEFAULT CHARACTER SET utf8mb4';
    }
}

$GLOBALS['wpdb'] = new TestWpdb();
$GLOBALS['wp_options'] = [];
