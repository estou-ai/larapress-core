<?php

namespace Larapress\Components;

 use Carbon\Carbon;

 trait Component
{
    private $dbCollumn = '';
    private $defaultValue = null;
    private $value = "";
    private $showOn = "";

    private $label = "";
    private $placeholder = "";
    public static function make($name)
    {
        return (new self())->setName($name);
    }

    public function setName($name)
    {
        $this->dbCollumn = $name;
        return $this;
    }

    public function setValue($value){
        if (property_exists($this, 'type')) {
            if ($this->type == "date") {
                if ($value instanceof Carbon)
                    $value = $value->format('Y-m-d');
            }
        }
        $this->value = $value;
        return $this;
    }

     public function setLabel($label)
     {
         $this->label = $label;
         return $this;
    }

     public function getLabel()
     {
         return $this->label;
     }

     public function getName()
     {
         return $this->dbCollumn;
     }

     public function showOn($action)
     {
         $this->showOn = $action;
         return $this;
     }

     public function canShow($currentAction)
     {
         if ($this->showOn == "" || $this->showOn == $currentAction) {
             return true;
         }else{
             return false;
         }
     }

     public function setPlaceholder($placeholder):self
     {
         $this->placeholder = $placeholder;

         return $this;
     }
 }