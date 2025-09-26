<?php

namespace Larapress\Components\Form;

use Larapress\Components\Component;

class Form
{
    /**
     * @var $schema Component[]
     */
    private $schema = [];
    public function schema(array $form = [])
    {
        $this->schema = $form;

        return $this;
    }

    public function getSchema()
    {
     return $this->schema;
    }

    public function fill($model)
    {
        foreach ($this->schema as $field) {
            $this->fillField($field, $model);
        }
    }

    /**
     * @param Component $field
     * @param $model
     * @return void
     */
    private function fillField($field, $model)
    {
        $name = $field->getName();

        if (isset($model[$name])) {
            $field->setValue($model[$name]);
        }
    }
}