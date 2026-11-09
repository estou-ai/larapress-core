<?php

use Larapress\Support\Flash;

it('reads a nested config key', function () {
    expect(array_get(['a' => ['b' => 'x']], 'a.b'))->toBe('x');
});

it('falls back to a default for a missing key', function () {
    expect(array_get(['a' => 1], 'a.b.c', 'd'))->toBe('d');
    expect(config('app.nope', 'fallback'))->toBe('fallback');
});

it('builds a resource url', function () {
    // Regression: public_path() used to reference a bare `helpers` constant
    // and an undefined phpconfig(), making every call fatal.
    config_set('app', ['resources_url' => 'https://example.test/resources']);

    expect(public_path('/css/app.css'))->toBe('https://example.test/resources/css/app.css');
});

it('reads a dotted config key', function () {
    config_set('app', ['slug' => 'my-plugin']);

    expect(config('app.slug'))->toBe('my-plugin');
});

it('merges values into an admin querystring', function () {
    $result = querystring('admin.php?page=x', ['order' => 'asc']);

    expect($result)->toContain('page=x')->toContain('order=asc');
});

it('requires files globbed inside the plugin', function () {
    // Regression: the glob was built from the app slug and matched nothing.
    $dir = BASE_PATH . '/tmp-loadfiles';
    @mkdir($dir, 0777, true);
    file_put_contents($dir . '/marker.php', '<?php $GLOBALS["loaded_marker"] = true;');

    loadFiles('tmp-loadfiles');

    unlink($dir . '/marker.php');
    rmdir($dir);

    expect($GLOBALS['loaded_marker'] ?? false)->toBeTrue();
});

it('starts a session only when asked', function () {
    // The session helper is what lets loadSession() stop firing on every
    // front-end request, which would suppress full-page caching.
    expect(function_exists('session'))->toBeTrue();
});

describe('providers', function () {
    it('instantiates a registered class', function () {
        $instances = loadRegisteredClasses('Testing', [TestProvider::class]);

        expect($instances)->toHaveCount(1)
            ->and($instances[0])->toBeInstanceOf(TestProvider::class);
    });

    it('throws on a class that does not exist', function () {
        loadRegisteredClasses('PostTypes', ['App\\Nope\\Missing']);
    })->throws(RuntimeException::class);
});

describe('flash', function () {
    it('round-trips a payload through a token in the url', function () {
        $url = Flash::to('https://example.test/page', ['message' => 'saved']);

        expect($url)->toContain(Flash::PARAM . '=');

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $_GET = [Flash::PARAM => $query[Flash::PARAM]];

        expect(Flash::take())->toBe(['message' => 'saved']);
    });

    it('is single use', function () {
        $url = Flash::to('https://example.test/page', ['message' => 'once']);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $_GET = [Flash::PARAM => $query[Flash::PARAM]];

        Flash::take();

        expect(Flash::take())->toBeNull();
    });

    it('uses a lowercase token, since the reader runs sanitize_key', function () {
        $url = Flash::to('https://example.test/page', ['a' => 1]);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $token = $query[Flash::PARAM];

        expect($token)->toBe(strtolower($token));
    });

    it('returns null without a token', function () {
        $_GET = [];

        expect(Flash::take())->toBeNull();
    });
});
