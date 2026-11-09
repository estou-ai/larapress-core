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
        return '<select name="' . esc_attr($this->dbCollumn) . '" id="' . esc_attr($this->dbCollumn) . '">
                '.$this->getOptions().'
            </select>';
    }

    /** Option values and labels are escaped; both can come from stored data. */
    private function getOptions()
    {
        $html = '';
        if ($this->defaultValue == "" && $this->value == "") {
            $html.= '<option value="" selected disabled>'
                . esc_html__('Select an option', 'larapress')
                . '</option>';
        }
        foreach ($this->options as $value => $label) {
            if ($label == ''){
                continue;
            }
            $selected = $this->isSelected($value)?' selected="selected"':'';
            $html .= '<option '.$selected.' value="' . esc_attr($value) . '">'
                . esc_html($label) . '</option>';
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