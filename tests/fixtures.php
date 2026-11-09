<?php

/** Fixture classes extending the framework bases; loaded after the autoloader. */

class TestProvider
{
}

class SlugProbe extends Larapress\Dependencies\BaseElement
{
    public function probeClass(): string
    {
        return $this->getClass();
    }

    public function probeSlugify(...$parts): string
    {
        return $this->slugify(...$parts);
    }
}

class TestSchema extends Larapress\Database\BaseSchema
{
    public function name(): string
    {
        return 'things';
    }

    public function definition(): string
    {
        return 'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY  (id)';
    }
}

class TestCron extends Larapress\Cron\BaseCron
{
    public function hook(): string
    {
        return 'test_cron_hook';
    }

    public function run(): void
    {
    }
}

/**
 * Stands in for an Eloquent builder: records what Table asked of it, so the
 * tests can assert on reorder/offset/limit without a database.
 */
class FakeQueryBuilder extends As247\WpEloquent\Database\Eloquent\Builder
{
    public array $orderBys = [];
    public array $reorders = [];
    public ?int $offsetValue = null;
    public ?int $limitValue = null;

    private array $rows;
    private int $count;

    public function __construct(array $rows = [], ?int $count = null)
    {
        $this->rows = $rows;
        $this->count = $count ?? count($rows);
    }

    public function toBase(): object
    {
        $count = $this->count;

        return new class($count) {
            public function __construct(private int $count) {}
            public function getCountForPagination(): int { return $this->count; }
        };
    }

    public function orderBy($column, $direction = 'asc'): static
    {
        $this->orderBys[] = [$column, $direction];
        return $this;
    }

    public function reorder($column = null, $direction = 'asc'): static
    {
        $this->reorders[] = [$column, $direction];
        return $this;
    }

    public function offset($value): static
    {
        $this->offsetValue = $value;
        return $this;
    }

    public function limit($value): static
    {
        $this->limitValue = $value;
        return $this;
    }

    public function get($columns = ['*']): object
    {
        $rows = $this->rows;

        return new class($rows) {
            public function __construct(private array $rows) {}
            public function all(): array { return $this->rows; }
        };
    }
}
