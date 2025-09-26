<?php

namespace Larapress\Components\Table;

class Table
{
    private array $schema = [];
    private array $data = [];
    private array $actions = [];

    public function __construct()
    {

    }

    public static function make(): Table
    {
        return new self();
    }

    public function schema(array $schema): self
    {
        $this->schema = $schema;
        return $this;
    }

    public function data(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function actions(array $actions): self
    {
        $this->actions = $actions;
        return $this;
    }

    public function getColumns(): array
    {
        return $this->schema;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function processData()
    {
        $order = $_GET['order']??null;

        $orderBy = $_GET['orderBy']??null;

        if (!isset($order) || !isset($orderBy)) {
            return;
        }

        $data = $this->data;

        usort($data, function ($a, $b) use ($order, $orderBy) {
                if ($order === 'asc') {
                    return $a[$orderBy] <=> $b[$orderBy];
                } else {
                    return $b[$orderBy] <=> $a[$orderBy];
                }
            });

        $this->data = $data;
    }

    public function getActions(): array
    {
        return $this->actions;
    }
}