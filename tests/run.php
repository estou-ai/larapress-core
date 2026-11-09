<?php

/**
 * Framework self-checks. No WordPress, no PHPUnit:
 *
 *   php tests/run.php
 *
 * Covers the pure logic that broke silently before — public_path(), the
 * loader glob, slug generation, column escaping, sort whitelisting — with
 * just enough WordPress stubbed to exercise it. Add a case here whenever a
 * bug escapes.
 */

// Stubs first: they define BASE_PATH and the WordPress functions that the
// framework's own helpers and classes call. Then the autoloader (whose
// helpers are all function_exists-guarded), then fixtures, which subclass
// the framework bases and so need the autoloader in place.
require_once __DIR__ . '/stubs.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/fixtures.php';

$passed = 0;
$failed = 0;

function it(string $label, callable $test): void
{
    global $passed, $failed;

    try {
        $test();
        printf("ok    %s\n", $label);
        $passed++;
    } catch (Throwable $e) {
        printf("FAIL  %s\n        %s\n", $label, $e->getMessage());
        $failed++;
    }
}

function assertSame($expected, $actual, string $what = ''): void
{
    if ($expected !== $actual) {
        throw new Exception(sprintf(
            '%sexpected %s, got %s',
            $what ? $what . ': ' : '',
            var_export($expected, true),
            var_export($actual, true)
        ));
    }
}

function assertTrue($value, string $what = 'expected true'): void
{
    if ($value !== true) {
        throw new Exception($what);
    }
}

function assertThrows(callable $fn, string $what = 'expected a throw'): void
{
    try {
        $fn();
    } catch (Throwable $e) {
        return;
    }

    throw new Exception($what);
}

// ---------------------------------------------------------------- helpers

it('array_get reads a nested key', function () {
    assertSame('x', array_get(['a' => ['b' => 'x']], 'a.b'));
});

it('array_get falls back to the default', function () {
    assertSame('d', array_get(['a' => 1], 'a.b.c', 'd'));
});

it('public_path joins the resources url (regression: was fatal)', function () {
    global $configs;
    $configs['app'] = ['resources_url' => 'https://example.test/resources'];

    assertSame('https://example.test/resources/css/app.css', public_path('/css/app.css'));
});

it('config reads a dotted key', function () {
    global $configs;
    $configs['app'] = ['slug' => 'my-plugin'];

    assertSame('my-plugin', config('app.slug'));
});

it('config returns the default for a missing key', function () {
    assertSame('fallback', config('app.nope', 'fallback'));
});

it('querystring merges values into the admin url', function () {
    $result = querystring('admin.php?page=x', ['order' => 'asc']);

    assertTrue(str_contains($result, 'page=x'), "kept page: $result");
    assertTrue(str_contains($result, 'order=asc'), "added order: $result");
});

// ---------------------------------------------------------------- loaders

it('loadFiles globs inside BASE_PATH (regression: globbed nowhere)', function () {
    $dir = BASE_PATH . '/tmp-loadfiles';
    @mkdir($dir, 0777, true);
    file_put_contents($dir . '/marker.php', '<?php $GLOBALS["loaded_marker"] = true;');

    loadFiles('tmp-loadfiles');

    unlink($dir . '/marker.php');
    rmdir($dir);

    assertTrue($GLOBALS['loaded_marker'] ?? false, 'the globbed file was required');
});

it('loadRegisteredClasses throws on a class that does not exist', function () {
    assertThrows(
        fn() => loadRegisteredClasses('PostTypes', ['App\\Nope\\Missing']),
        'a bad provider name must throw, not load nothing'
    );
});

it('loadRegisteredClasses instantiates a listed class', function () {
    $instances = loadRegisteredClasses('Testing', [TestProvider::class]);

    assertSame(1, count($instances));
    assertTrue($instances[0] instanceof TestProvider);
});

// ------------------------------------------------------------ BaseElement

it('getClass turns a class name into a snake slug', function () {
    assertSame('slug_probe', (new SlugProbe())->probeClass());
});

it('slugify joins parts with the separator', function () {
    assertSame('a_b_c', (new SlugProbe())->probeSlugify('a', 'b', 'c'));
});

// ----------------------------------------------------------------- Column

it('a column escapes its value by default', function () {
    $column = Larapress\Components\Table\Column::make('name');

    assertSame(
        '&lt;script&gt;alert(1)&lt;/script&gt;',
        $column->getRenderedValue(['name' => '<script>alert(1)</script>'])
    );
});

it('html() opts a column out of escaping', function () {
    $column = Larapress\Components\Table\Column::make('link')->html();

    assertSame('<a href="#">go</a>', $column->getRenderedValue(['link' => '<a href="#">go</a>']));
});

it('a column escapes a setGetValue result too', function () {
    $column = Larapress\Components\Table\Column::make('name')
        ->setGetValue(fn(array $row) => $row['name']);

    assertSame('&lt;b&gt;x&lt;/b&gt;', $column->getRenderedValue(['name' => '<b>x</b>']));
});

it('a missing value renders as a dash', function () {
    $column = Larapress\Components\Table\Column::make('absent');

    assertSame('-', $column->getRenderedValue(['other' => 1]));
});

it('an array value is joined, not printed as Array', function () {
    $column = Larapress\Components\Table\Column::make('guests')
        ->setGetValue(fn(array $row) => $row['guests']);

    assertSame('Ana, Rui', $column->getRenderedValue(['guests' => ['Ana', 'Rui']]));
});

// ------------------------------------------------------------------ Table

it('sorting is limited to columns declared sortable', function () {
    $table = Larapress\Components\Table\Table::make()
        ->schema([
            Larapress\Components\Table\Column::make('name')->sortable(),
            Larapress\Components\Table\Column::make('secret'),
        ])
        ->data([
            ['name' => 'b', 'secret' => 1],
            ['name' => 'a', 'secret' => 2],
        ]);

    $_GET = ['orderBy' => 'name', 'order' => 'asc'];
    $table->processData();
    assertSame('a', $table->getData()[0]['name'], 'sortable column sorts');

    $table2 = Larapress\Components\Table\Table::make()
        ->schema([Larapress\Components\Table\Column::make('secret')])
        ->data([['secret' => 2], ['secret' => 1]]);

    $_GET = ['orderBy' => 'secret', 'order' => 'asc'];
    $table2->processData();
    assertSame(2, $table2->getData()[0]['secret'], 'non-sortable column is ignored');

    $_GET = [];
});

it('pagination is off for a plain data array', function () {
    $table = Larapress\Components\Table\Table::make()
        ->schema([Larapress\Components\Table\Column::make('a')])
        ->data([['a' => 1], ['a' => 2]]);

    $table->processData();

    assertSame(2, $table->getTotal());
    assertSame(false, $table->isPaginated());
});

it('a query table replaces the caller ordering instead of appending', function () {
    // Regression: appending left the requested column as a tie-breaker, so
    // clicking a column header did nothing.
    $query = new FakeQueryBuilder([['name' => 'b'], ['name' => 'a']]);

    $table = Larapress\Components\Table\Table::make()
        ->schema([Larapress\Components\Table\Column::make('name')->sortable()])
        ->query($query);

    $_GET = ['orderBy' => 'name', 'order' => 'asc'];
    $table->processData();
    $_GET = [];

    assertSame([['name', 'asc']], $query->reorders, 'reorder() was used');
    assertSame([], $query->orderBys, 'orderBy() was not appended');
});

it('a query table ignores a non-sortable orderBy', function () {
    $query = new FakeQueryBuilder([['secret' => 1]]);

    $table = Larapress\Components\Table\Table::make()
        ->schema([Larapress\Components\Table\Column::make('secret')])
        ->query($query);

    $_GET = ['orderBy' => 'secret; DROP TABLE wp_posts', 'order' => 'asc'];
    $table->processData();
    $_GET = [];

    assertSame([], $query->reorders, 'nothing reached the ORDER BY clause');
});

it('a query table paginates with offset and limit', function () {
    $query = new FakeQueryBuilder([['a' => 1]], 60);

    $table = Larapress\Components\Table\Table::make()
        ->schema([Larapress\Components\Table\Column::make('a')])
        ->query($query)
        ->perPage(50);

    $_GET = ['paged' => '2'];
    $table->processData();
    $_GET = [];

    assertSame(60, $table->getTotal());
    assertSame(50, $query->offsetValue);
    assertSame(50, $query->limitValue);
    assertTrue($table->isPaginated());
});

it('currentPage reads ?paged and never drops below 1', function () {
    $table = Larapress\Components\Table\Table::make();

    $_GET = ['paged' => '3'];
    assertSame(3, $table->currentPage());

    $_GET = ['paged' => '-5'];
    assertSame(1, $table->currentPage());

    $_GET = [];
});

// --------------------------------------------------------- BasePostType

it('sanitizeDateTime normalises a datetime-local value', function () {
    assertSame(
        '2026-12-24 19:30:00',
        Larapress\PostTypes\BasePostType::sanitizeDateTime('2026-12-24T19:30')
    );
});

it('sanitizeDateTime returns empty for junk and for blank', function () {
    assertSame('', Larapress\PostTypes\BasePostType::sanitizeDateTime('not a date'));
    assertSame('', Larapress\PostTypes\BasePostType::sanitizeDateTime('   '));
});

// ------------------------------------------------------------- BaseSchema

it('a schema derives its table name from the wpdb prefix', function () {
    assertSame('wp_things', (new TestSchema())->table());
});

it('a schema namespaces its version option', function () {
    assertSame('larapress_schema_things', (new TestSchema())->versionOption());
});

// --------------------------------------------------------------- BaseCron

it('a cron registers its handler on construction', function () {
    global $wp_actions_registered;
    $wp_actions_registered = [];

    new TestCron();

    assertTrue(
        in_array('test_cron_hook', $wp_actions_registered, true),
        'the hook was registered'
    );
});

it('a cron defaults to a daily recurrence', function () {
    assertSame('daily', (new TestCron())->recurrence());
});

// ----------------------------------------------------------------- report

printf("\n%d passed, %d failed\n", $passed, $failed);

exit($failed === 0 ? 0 : 1);
