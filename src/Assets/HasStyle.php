<?php

namespace Larapress\Assets;

trait HasStyle
{
    protected function loadCss(): void
    {
        foreach ($this->css() as $key => $value) {
            [$handle, $options] = Asset::normalize($key, $value, 'css');

            wp_enqueue_style(
                $handle,
                $options['src'],
                $options['deps'],
                $options['version'],
                $options['media']
            );
        }
    }

    /**
     * Stylesheets to enqueue.
     *
     * Short form, resolved to resources/css/<name>.css:
     *   return ['event-form'];
     *
     * Long form, for a version, dependencies, media or an external URL:
     *   return [
     *       'event-form' => ['version' => '2.1'],
     *       'print' => ['media' => 'print'],
     *       'vendor-grid' => ['src' => 'https://cdn.example/grid.css'],
     *   ];
     */
    public function css():array
    {
        return [];
    }
}