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
        $path = $internalView
            ? __DIR__ . '/../../resources/Views/errors.php'
            : self::resolveView($view);

        // Extraction runs inside a static closure that holds no locals worth
        // clobbering: in the old in-scope loop, a data key named `view` or
        // `internalView` overwrote the parameters and chose the include
        // target itself.
        //
        // require, not require_once: the same shortcode may legitimately
        // appear twice on one page, and require_once rendered it blank the
        // second time.
        $render = static function (string $__path, array $__data): string {
            foreach ($__data as $__name => $__value) {
                if (!is_string($__name) || !preg_match('/^[a-zA-Z_]\w*$/', $__name)) {
                    continue;
                }

                ${$__name} = $__value;
            }

            unset($__name, $__value, $__data);

            ob_start();

            require $__path;

            return (string) ob_get_clean();
        };

        $html = $render($path, (array) $data);

        $this->enqueue();

        return $html;
    }

    /**
     * Resolves a view name against the configured views directory, refusing
     * anything that escapes it.
     *
     * Callers pass a literal today, but one deriving the name from a
     * shortcode attribute would otherwise have a local file include.
     */
    private static function resolveView(string $view): string
    {
        $base = (string) config('app.views');

        $real = realpath($base . '/' . ltrim($view, '/'));
        $realBase = realpath($base);

        if ($real === false || $realBase === false || !str_starts_with($real, $realBase)) {
            throw new \RuntimeException(sprintf('View "%s" is not inside the views directory.', $view));
        }

        return $real;
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
