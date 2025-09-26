<?php

namespace Larapress\Components\Form;

use Larapress\Components\Component;

class Select
{
    use Component;
    private $options = [];

    /**
     * @param $options
     * @return $this
     */
    public function setOptions(array $options= [])
    {

        $this->options = $options;
        return $this;
    }

    public function setDefaultValue($value)
    {
        $this->defaultValue = $value;
        return $this;
    }

    public function render()
    {
        return '<select name="'.$this->dbCollumn.'" id="'.$this->dbCollumn.'">
                '.$this->getOptions().'
            </select>';
    }

    private function getOptions()
    {
        $html = '';
        if ($this->defaultValue == "" && $this->value == "") {
            $html.= '<option value="" selected disabled>Select an option</option>';
        }
        foreach ($this->options as $value => $label) {
            if ($label == ''){
                continue;
            }
            $selected = $this->isSelected($value)?' selected="selected"':'';
            $html .= '<option '.$selected.' value="'.$value.'">'.$label.'</option>';
        }
        return $html;
    }

    private function isSelected($key): bool
    {
        if ($this->value === $key){
            return true;
        }

        if($this->value === ''){
            if ($this->defaultValue === $key){
                return true;
            }
        }
        return false;
    }
}