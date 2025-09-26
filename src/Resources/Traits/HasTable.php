<?php

namespace Larapress\Resources\Traits;

trait HasTable
{
    private string $tableView = BASE_PATH.'/vendor/larapress/core/resources/Views/table.php';
    /**
     * @throws \Exception
     */
    public function renderTable(): void
    {
        if (!method_exists($this, 'getTable')){
            throw new \Exception('Method getTable() is not implemented');
        }

        $table = $this->getTable();

        $columns = $table->getColumns();

        $table->processData();

        $data = $table->getData();

        require_once $this->tableView;

    }
}