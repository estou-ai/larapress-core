<?php

namespace Larapress\Components\Table;

use Carbon\Carbon;

class Column
{
    private string $name;
    private string $label;

    private bool $isSortable = false;
    private bool $isDate = false;
    private bool $isHtml = false;

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

    /**
     * Renders this column's value as raw HTML instead of escaping it.
     *
     * Only for markup the column itself builds, such as a link assembled in
     * setGetValue(). Never for a value that came from a request or the
     * database, since that is stored XSS.
     */
    public function html(bool $isHtml = true): self
    {
        $this->isHtml = $isHtml;
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

    /** The raw value, unescaped. Use getRenderedValue() to print it. */
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

    /**
     * The value escaped for output, which is what the table view prints.
     *
     * Escaping by default means a name or e-mail submitted through a public
     * form cannot inject markup into the admin. Columns that genuinely build
     * markup opt out with html().
     */
    public function getRenderedValue(array $entry): string
    {
        $value = $this->getValue($entry);

        if ($this->isHtml) {
            return (string) $value;
        }

        if (is_array($value)) {
            $value = implode(', ', $value);
        }

        return esc_html((string) $value);
    }

    public function setGetValue(callable $callback)
    {
        $this->getValue = $callback;
        return $this;
    }
}