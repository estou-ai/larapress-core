<?php

namespace Larapress\PostTypes;

/**
 * A custom post type with a meta box driven by one field declaration.
 *
 * Instances are created by loadPostTypes() from the bootstrap chain, outside
 * the is_admin() gate that wraps loadAdmin(): register_post_type must run on
 * the front end too, or permalinks and public queries break.
 *
 * Each entry in fields() is:
 *   ['id' => 'capacity', 'type' => 'integer', 'label' => 'Seats']
 * where type is one of integer, datetime, text or textarea.
 */
abstract class BasePostType
{
    abstract public function type(): string;

    abstract public function labels(): array;

    /** @return array<int,array{id:string,type:string,label:string}> */
    public function fields(): array
    {
        return [];
    }

    public function args(): array
    {
        return [];
    }

    public function metaBoxTitle(): string
    {
        return __('Details', 'larapress');
    }

    /**
     * Hooks are registered unconditionally and each handler checks fields()
     * when it runs.
     *
     * Calling fields() here instead would read field labels at construction
     * time — before `init`, and so before any text domain is loaded, which
     * makes WordPress emit a "translation loading was triggered too early"
     * notice and return untranslated strings.
     */
    public function __construct()
    {
        add_action('init', [$this, 'register']);
        add_action('init', [$this, 'registerMeta']);
        add_action('add_meta_boxes', [$this, 'registerMetaBox']);
        add_action('save_post_' . $this->type(), [$this, 'saveMeta'], 10, 2);
    }

    public function register(): void
    {
        register_post_type($this->type(), array_merge([
            'labels' => $this->labels(),
            'public' => true,
            'supports' => ['title', 'editor', 'thumbnail', 'revisions'],
            'show_in_rest' => true,
        ], $this->args()));
    }

    public function registerMeta(): void
    {
        foreach ($this->fields() as $field) {
            register_post_meta($this->type(), $field['id'], [
                'single' => true,
                'type' => $field['type'] === 'integer' ? 'integer' : 'string',
                'description' => $field['label'] ?? $field['id'],
                'show_in_rest' => false,
                // Per-post, matching saveMeta's own check: the generic
                // edit_posts let any contributor write meta onto a post
                // belonging to someone else.
                'auth_callback' => static fn($allowed, $key, $postId)
                    => current_user_can('edit_post', $postId),
            ]);
        }
    }

    public function registerMetaBox(): void
    {
        if (!$this->fields()) {
            return;
        }

        add_meta_box(
            $this->type() . '_meta',
            $this->metaBoxTitle(),
            [$this, 'renderMetaBox'],
            $this->type(),
            'normal',
            'high'
        );
    }

    public function renderMetaBox($post): void
    {
        wp_nonce_field($this->nonceAction(), $this->nonceAction());

        echo '<table class="form-table"><tbody>';

        foreach ($this->fields() as $field) {
            $id = esc_attr($field['id']);
            $value = get_post_meta($post->ID, $field['id'], true);

            echo '<tr><th scope="row"><label for="' . $id . '">'
                . esc_html($field['label'] ?? $field['id'])
                . '</label></th><td>';

            if ($field['type'] === 'textarea') {
                echo '<textarea id="' . $id . '" name="' . $id . '" rows="3" class="large-text">'
                    . esc_textarea($value) . '</textarea>';
            } else {
                $type = match ($field['type']) {
                    'integer' => 'number',
                    'datetime' => 'datetime-local',
                    default => 'text',
                };

                echo '<input type="' . $type . '" id="' . $id . '" name="' . $id . '"'
                    . ' value="' . esc_attr($value) . '" min="0" class="regular-text">';
            }

            echo '</td></tr>';
        }

        echo '</tbody></table>';

        $this->afterMetaBox($post);
    }

    /** Hook for subclasses to append extra markup below the field table. */
    protected function afterMetaBox($post): void
    {
    }

    public function saveMeta($postId, $post): void
    {
        if (!$this->fields()) {
            return;
        }

        if (wp_is_post_autosave($postId) || wp_is_post_revision($postId)) {
            return;
        }

        $nonce = $this->nonceAction();

        if (!isset($_POST[$nonce]) || !wp_verify_nonce($_POST[$nonce], $nonce)) {
            return;
        }

        if (!current_user_can('edit_post', $postId)) {
            return;
        }

        foreach ($this->fields() as $field) {
            $id = $field['id'];

            if (!isset($_POST[$id])) {
                continue;
            }

            $raw = wp_unslash($_POST[$id]);

            $value = match ($field['type']) {
                'integer' => (string) max(0, (int) $raw),
                'datetime' => self::sanitizeDateTime($raw),
                'textarea' => sanitize_textarea_field($raw),
                default => sanitize_text_field($raw),
            };

            if ($value === '') {
                delete_post_meta($postId, $id);
                continue;
            }

            update_post_meta($postId, $id, $value);
        }
    }

    /** Normalises a datetime-local value to UTC Y-m-d H:i:s, or '' if unusable. */
    public static function sanitizeDateTime($raw): string
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return '';
        }

        $timestamp = strtotime($raw);

        return $timestamp === false ? '' : gmdate('Y-m-d H:i:s', $timestamp);
    }

    protected function nonceAction(): string
    {
        return $this->type() . '_meta';
    }
}
