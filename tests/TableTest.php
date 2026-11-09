<?php

use Larapress\Components\Table\Column;
use Larapress\Components\Table\Table;

afterEach(fn() => $_GET = []);

describe('in-memory data', function () {
    it('sorts a column declared sortable', function () {
        $table = Table::make()
            ->schema([Column::make('name')->sortable()])
            ->data([['name' => 'b'], ['name' => 'a']]);

        $_GET = ['orderBy' => 'name', 'order' => 'asc'];
        $table->processData();

        expect($table->getData()[0]['name'])->toBe('a');
    });

    it('ignores a column that is not sortable', function () {
        $table = Table::make()
            ->schema([Column::make('secret')])
            ->data([['secret' => 2], ['secret' => 1]]);

        $_GET = ['orderBy' => 'secret', 'order' => 'asc'];
        $table->processData();

        expect($table->getData()[0]['secret'])->toBe(2);
    });

    it('does not paginate', function () {
        $table = Table::make()
            ->schema([Column::make('a')])
            ->data([['a' => 1], ['a' => 2]]);

        $table->processData();

        expect($table->getTotal())->toBe(2)
            ->and($table->isPaginated())->toBeFalse();
    });
});

describe('query-backed data', function () {
    it('replaces the caller ordering rather than appending to it', function () {
        // Regression: appending left the requested column as a tie-breaker,
        // so clicking a column header did nothing.
        $query = new FakeQueryBuilder([['name' => 'b'], ['name' => 'a']]);

        $table = Table::make()
            ->schema([Column::make('name')->sortable()])
            ->query($query);

        $_GET = ['orderBy' => 'name', 'order' => 'asc'];
        $table->processData();

        expect($query->reorders)->toBe([['name', 'asc']])
            ->and($query->orderBys)->toBe([]);
    });

    it('keeps a crafted orderBy out of the ORDER BY clause', function () {
        $query = new FakeQueryBuilder([['secret' => 1]]);

        $table = Table::make()
            ->schema([Column::make('secret')])
            ->query($query);

        $_GET = ['orderBy' => 'secret; DROP TABLE wp_posts', 'order' => 'asc'];
        $table->processData();

        expect($query->reorders)->toBe([]);
    });

    it('pages with offset and limit', function () {
        $query = new FakeQueryBuilder([['a' => 1]], 60);

        $table = Table::make()
            ->schema([Column::make('a')])
            ->query($query)
            ->perPage(50);

        $_GET = ['paged' => '2'];
        $table->processData();

        expect($table->getTotal())->toBe(60)
            ->and($query->offsetValue)->toBe(50)
            ->and($query->limitValue)->toBe(50)
            ->and($table->isPaginated())->toBeTrue();
    });

    it('only accepts asc or desc as a direction', function () {
        $query = new FakeQueryBuilder([]);

        Table::make()
            ->schema([Column::make('a')->sortable()])
            ->query($query)
            ->processData();

        $_GET = ['orderBy' => 'a', 'order' => 'nonsense'];

        $query2 = new FakeQueryBuilder([]);
        Table::make()
            ->schema([Column::make('a')->sortable()])
            ->query($query2)
            ->processData();

        expect($query2->reorders)->toBe([['a', 'desc']]);
    });
});

describe('paging arithmetic', function () {
    it('reads ?paged and never drops below one', function () {
        $table = Table::make();

        $_GET = ['paged' => '3'];
        expect($table->currentPage())->toBe(3);

        $_GET = ['paged' => '-5'];
        expect($table->currentPage())->toBe(1);
    });
});

describe('row actions', function () {
    it('resolves a url per row', function () {
        $table = Table::make()
            ->schema([Column::make('id')])
            ->actions(['Edit' => fn(array $row) => '/edit/' . $row['id']]);

        expect($table->hasActions())->toBeTrue()
            ->and($table->actionsFor(['id' => 7]))
            ->toBe([['label' => 'Edit', 'url' => '/edit/7']]);
    });

    it('reports no actions when none were declared', function () {
        expect(Table::make()->hasActions())->toBeFalse();
    });
});
