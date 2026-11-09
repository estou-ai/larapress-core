<?php

namespace Larapress\Assets;

trait HasJavascript
{
    public  bool $useJquery = false;
    public array $javascriptVariables = [];
    public function addVariable(string $varName,string $scriptName, mixed $value): void
    {
        if (!is_array($value))
            $value=[$value];
        $this->javascriptVariables[$varName] = [
            'value' => $value,
            'script' => $scriptName
        ];
    }

    protected function enableJquery($enable = true): void
    {
        $this->useJquery = $enable;
    }

    private function renderJsVariables():void
    {
        foreach ($this->javascriptVariables as $varName => $value) {
            wp_localize_script($value['script'], $varName, $value['value']);
        }
    }

    protected function loadJs(): void
    {
        if ($this->useJquery){
            wp_enqueue_script('jquery');
        }

        foreach ($this->js() as $key => $value) {
            [$handle, $options] = Asset::normalize($key, $value, 'js');

            wp_enqueue_script(
                $handle,
                $options['src'],
                $options['deps'],
                $options['version'],
                $options['in_footer']
            );
        }

        $this->renderJsVariables();
    }

    /**
     * Scripts to enqueue.
     *
     * Short form, resolved to resources/js/<name>.js:
     *   return ['event-form'];
     *
     * Long form, for dependencies, a version, head placement or a CDN:
     *   return [
     *       'event-form' => ['deps' => ['jquery']],
     *       'turnstile' => [
     *           'src' => 'https://challenges.cloudflare.com/turnstile/v0/api.js',
     *           'version' => null,
     *       ],
     *       'early' => ['in_footer' => false],
     *   ];
     */
    public function js():array
    {
        return [];
    }
}
