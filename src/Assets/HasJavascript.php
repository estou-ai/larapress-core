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

        $js_files = $this->js();

        foreach ($js_files as $file) {
            wp_enqueue_script($file, public_path('/js/'. $file.'.js'));
        }

        $this->renderJsVariables();
    }

    public function js():array
    {
        return [];
    }
}
