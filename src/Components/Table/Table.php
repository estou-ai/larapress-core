<?php

namespace Larapress\Components\Table;

use As247\WpEloquent\Database\Eloquent\Builder;

class Table
{
    private array $schema = [];
    private array $data = [];
    private array $actions = [];

    private ?Builder $query = null;
    private int $perPage = 50;
    private int $total = 0;
    private bool $paginated = false;
    private ?string $actionsLabel = null;
    private array $filters = [];
    private array $hiddenParams = [];

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
        $this->total = count($data);
        return $this;
    }

    /**
     * Sorts and paginates in the database instead of in PHP.
     *
     * Prefer this over data() for anything that grows: data() loads every row
     * into memory and sorts with usort, which stops being viable in the
     * thousands.
     */
    public function query(Builder $query): self
    {
        $this->query = $query;
        return $this;
    }

    public function perPage(int $perPage): self
    {
        $this->perPage = max(1, $perPage);
        return $this;
    }

    /**
     * Per-row links rendered in a trailing Actions column.
     *
     * Each entry is a label and a callable returning the URL for that row:
     *   ->actions([
     *       'Edit' => fn(array $row) => self::getRoute('edit', ['id' => $row['id']]),
     *       'Delete' => fn(array $row) => self::actionUrl('delete', ['id' => $row['id']]),
     *   ])
     *
     * Build a destructive URL with BaseResource::actionUrl(), which carries
     * the nonce its guard requires — and declare ['method' => 'get'] on that
     * action, since the guard answers POST unless told otherwise.
     *
     * @param array<string,callable> $actions
     */
    public function actions(array $actions): self
    {
        $this->actions = $actions;
        return $this;
    }

    /** Resolved [label, url] pairs for one row. */
    public function actionsFor(array $entry): array
    {
        $links = [];

        foreach ($this->actions as $label => $url) {
            $links[] = [
                'label' => (string) $label,
                'url' => is_callable($url) ? (string) $url($entry) : (string) $url,
            ];
        }

        return $links;
    }

    /** Column heading for the actions column. */
    public function actionsLabel(): string
    {
        return $this->actionsLabel ?? __('Actions', 'larapress');
    }

    public function setActionsLabel(string $label): self
    {
        $this->actionsLabel = $label;
        return $this;
    }

    public function hasActions(): bool
    {
        return $this->actions !== [];
    }

    /**
     * Filter controls rendered above the table, as [key => definition]:
     *
     *   'status' => ['label' => 'Estado', 'options' => ['open' => 'Abertos']],
     *   's' => ['label' => 'Procurar', 'type' => 'search'],
     *
     * The current values arrive in $_GET under the same keys; the caller
     * applies them to its own query, since only it knows what they mean.
     */
    public function filters(array $filters): self
    {
        $this->filters = $filters;
        return $this;
    }

    /**
     * Query parameters carried through the filter form untouched, such as a
     * record id the page is already scoped to. Without them, filtering drops
     * that scope.
     */
    public function keepParams(array $params): self
    {
        $this->hiddenParams = $params;
        return $this;
    }

    public function getKeptParams(): array
    {
        return $this->hiddenParams;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function hasFilters(): bool
    {
        return $this->filters !== [];
    }

    /** The submitted value for a filter, or '' when it is not set. */
    public static function filterValue(string $key): string
    {
        $value = $_GET[$key] ?? '';

        return is_string($value) ? sanitize_text_field(wp_unslash($value)) : '';
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
        if ($this->query !== null) {
            $this->processQuery();
            return;
        }

        $order = $_GET['order']??null;

        $orderBy = $_GET['orderBy']??null;

        if (!isset($order) || !isset($orderBy)) {
            return;
        }

        if (!$this->isSortable($orderBy)) {
            return;
        }

        $data = $this->data;

        usort($data, function ($a, $b) use ($order, $orderBy) {
                if ($order === 'asc') {
                    return ($a[$orderBy] ?? null) <=> ($b[$orderBy] ?? null);
                } else {
                    return ($b[$orderBy] ?? null) <=> ($a[$orderBy] ?? null);
                }
            });

        $this->data = $data;
    }

    private function processQuery(): void
    {
        $query = $this->query;

        $this->total = (int) $query->toBase()->getCountForPagination();

        $orderBy = $_GET['orderBy'] ?? null;
        $order = ($_GET['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        // Only a declared sortable column may reach the ORDER BY clause, so a
        // crafted ?orderBy= cannot inject SQL.
        //
        // reorder() rather than orderBy(): the caller's own ordering is
        // already on the query, and appending to it would leave the
        // requested column as a mere tie-breaker.
        if ($orderBy !== null && $this->isSortable($orderBy)) {
            $query->reorder($orderBy, $order);
        }

        $offset = ($this->currentPage() - 1) * $this->perPage;

        $rows = $query->offset($offset)->limit($this->perPage)->get();

        $this->data = array_map(
            fn($row) => is_array($row) ? $row : $row->toArray(),
            $rows->all()
        );

        $this->paginated = true;
    }

    private function isSortable(string $name): bool
    {
        foreach ($this->schema as $column) {
            if ($column->getName() === $name) {
                return $column->getIsSortable();
            }
        }

        return false;
    }

    public function currentPage(): int
    {
        return max(1, (int) ($_GET['paged'] ?? 1));
    }

    public function totalPages(): int
    {
        return (int) ceil($this->total / max(1, $this->perPage));
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function isPaginated(): bool
    {
        return $this->paginated && $this->totalPages() > 1;
    }

    public function getActions(): array
    {
        return $this->actions;
    }
}
