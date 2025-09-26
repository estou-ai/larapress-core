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

    public function execute($args)
    {
        $this->render();
    }

    public function render()
    {
        return '';
    }
}