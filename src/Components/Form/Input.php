<?php

namespace Larapress\Components\Form;

use Larapress\Components\Component;

class Input
{
    use Component;
    private $type = 'text';

    public function setDefaultValue($value): Input
    {
        $this->defaultValue = $value;
        return $this;
    }

    public function setType($type): Input
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Every attribute is escaped: $value arrives from the model or, after a
     * failed submit, straight from $_POST, so an unescaped value could close
     * the attribute and inject markup.
     */
    public function render(): string
    {
        return '<input placeholder="' . esc_attr($this->placeholder) . '"'
            . '  class="regular-text"'
            . ' type="' . esc_attr($this->type) . '"'
            . ' name="' . esc_attr($this->dbCollumn) . '"'
            . ' id="' . esc_attr($this->dbCollumn) . '"'
            . ' value="' . esc_attr($this->value) . '"></input>';
    }
}