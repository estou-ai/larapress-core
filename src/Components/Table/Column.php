<?php

namespace Larapress\Components\Table;

use Carbon\Carbon;

class Column
{
    private string $name;
    private string $label;

    private bool $isSortable = false;
    private bool $isDate = false;

    private string $dateFormat='Y-m-d';

    private $getValue;

    public function __construct($name=null)
    {
        $this->name = $name;
    }

    public static function make($name=null)
    {
        return new self($name);
    }

    public function sortable(bool $sortable = true): self
    {
        $this->isSortable = $sortable;
        return $this;
    }
    public function date(bool $isDate = true): self
    {
        $this->isDate = $isDate;
        return $this;
    }

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function label(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function dateFormat(string $dateFormat): self
    {
        $this->dateFormat = $dateFormat;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }
    public function getIsDate(): string
    {
        return $this->isDate;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIsSortable(): bool
    {
        return $this->isSortable;
    }

    public function getDateFormat(): bool
    {
        return $this->dateFormat;
    }

    public function getValue(array $entry)
    {
        $name = $this->getName();

        if (isset($this->getValue)){
            $callback = $this->getValue;
            return $callback($entry);
        }

        if (isset($entry[$name])) {
            if ($entry[$name] == null){
                return '-';
            }

            if($this->isDate){
                return Carbon::make($entry[$name])->format($this->dateFormat);
            }

            return $entry[$name];
        }else{
            return '-';
        }
    }

    public function setGetValue(callable $callback)
    {
        $this->getValue = $callback;
        return $this;
    }
}