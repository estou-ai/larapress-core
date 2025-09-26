<?php

namespace Larapress\Assets;

trait HasStyle
{
    protected function loadCss(): void
    {
        $css_files = $this->css();

        foreach ($css_files as $file) {
            wp_enqueue_style($file, public_path('/css/' . $file.'.css'));
        }
    }

    public function css():array
    {
        return [];
    }
}