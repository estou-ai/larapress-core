<?php

namespace Larapress\Resources\Traits;

trait HasTable
{
    private string $tableView = BASE_PATH.'/vendor/larapress/core/resources/Views/table.php';
    /**
     * Renders the table.
     *
     * @param bool $standalone Wrap it in .wrap with the page title. Pass
     *                         false when the page already printed its own
     *                         heading, or it appears twice.
     * @param string|null $emptyMessage Shown in place of rows when there are
     *                                  none; an empty table otherwise reads
     *                                  as a broken one.
     *
     * @throws \Exception
     */
    public function renderTable(bool $standalone = true, ?string $emptyMessage = null): void
    {
        if (!method_exists($this, 'getTable')){
            throw new \Exception('Method getTable() is not implemented');
        }

        $table = $this->getTable();

        $columns = $table->getColumns();

        $table->processData();

        $data = $table->getData();

        // require, not require_once: a page may render more than one table.
        require $this->tableView;

    }
}