<?php

namespace Larapress\ShortCodes;

use Larapress\Dependencies\BaseElement;
use Rakit\Validation\Validator;

abstract class BaseShortCode extends BaseElement
{
    protected string $separator = '-';

    public string $shortCodeTag;

    public function __construct()
    {
        $this->generateShortCode();
    }

    protected function generateShortCode(): void
    {
        $tag = $this->generateTag();
        add_shortcode($tag, [$this, 'execute']);
    }

    protected function generateTag(): string
    {
        $shortCode = $this->shortCodeTag ?? $this->getClass();
        return $this->slugify($shortCode);
    }

    protected function render(string $view, $data, $internalView = false): string
    {
        foreach ($data as $name => $value) {
            $$name = $value;
        }
        ob_start();
        ob_clean();

        if ($internalView) {
            require_once __DIR__ . '/../../resources/Views/errors.php';
        } else {
            require_once config('app.views') . $view;
        }

        $html = ob_get_contents();
        ob_clean();
        $this->enqueue();
        //add_action('wp_enqueue_scripts', [$this, 'enqueue']);

        return $html;
    }

    protected function validation(array $rules, array $data): false|string
    {
        $validator = new Validator();
        $validation = $validator->validate($data, $rules);

        if ($validation->fails()) {
            $errors = $validation->errors();
            $shortcode = $this->generateShortCodeSample($rules);
            return $this->renderError(['errors' => implode(' ', $errors->all('<li>:message</li>')), 'shortcode' => $shortcode]);
        }
        return false;
    }

    private function generateShortcodeParam(array $rules): array
    {
        $keys = array_keys($rules);

        $params = [];
        foreach ($keys as $key) {
            if (str_contains('required', $rules[$key]))
                $params[$key] = 'required_param';
        }

        return $params;
    }

    private function generateShortCodeSample($rules): string
    {
        $params = $this->generateShortCodeParam($rules);

        $shortcode = $this->generateTag();

        $paramsString = '';

        foreach ($params as $variable => $value) {
            $paramsString .= $variable . '="' . $value . '" ';
        }

        return '[' . $shortcode . ' ' . $paramsString . ']';
    }

    protected function renderError($details): false|string
    {
        if (current_user_can('administrator')) {
            return $this->render('errors.php', $details, true);
        }
        return "<div></div>";
    }

    public function css(): array
    {
        return [];
    }

    public static function getShortcode()
    {
        $instance = new static();
        return $instance->generateTag();
    }
}
