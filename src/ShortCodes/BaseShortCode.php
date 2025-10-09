<?php

namespace Larapress\ShortCodes;

use Larapress\Dependencies\BaseElement;

abstract class BaseShortCode extends BaseElement
{
    protected string $separator = '-';
    public function __construct()
    {
        $this->generaShortCode();
    }

    public function generaShortCode()
    {
        $tag = $this->generateTag();
        add_shortcode($tag,[$this, 'execute']);
    }

    public function generateTag()
    {
        return $this->slugify(config('app.slug'), $this->getClass());
    }

    public function execute($args):false|string
    {
        $this->render();
    }

    public function render()
    {
        return '';
    }

    protected function renderError($details): false|string
    {
        if (current_user_can('administrator')) {
            return $this->render('errors.php', $details, true);
        }
        return "<div></div>";
    }

    private function generateShortCodeSample($rules): string
    {
        $params = $this->generateShortCodeParam($rules);

        $shortcode = $this->generateTag();

        $paramsString = '';

        foreach ($params as $variable => $value) {
            $paramsString .= $variable.'="'.$value.'" ';
        }

        return '['.$shortcode.' '.$paramsString.']';
    }

    protected function validation(array $rules, array $data): false|string
    {
        $validator = new Validator();
        $validation = $validator->validate($data, $rules);

        if ($validation->fails()){
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
            if (str_contains('required',$rules[$key]))
                $params[$key] = 'required_param';
        }

        return $params;
    }


}
