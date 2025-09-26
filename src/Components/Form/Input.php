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

    public function render(): string
    {
        return '<input placeholder="'.$this->placeholder.'"  class="regular-text" type="'.$this->type.'" name="' . $this->dbCollumn . '" id="' . $this->dbCollumn . '" value="' . $this->value . '"></input>';
    }
}