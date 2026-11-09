<?php

use Larapress\Assets\Asset;
use Larapress\Dependencies\BaseElement;
use Larapress\PostTypes\BasePostType;

describe('element naming', function () {
    it('turns a class name into a snake slug', function () {
        expect((new SlugProbe())->probeClass())->toBe('slug_probe');
    });

    it('joins parts with the separator', function () {
        expect((new SlugProbe())->probeSlugify('a', 'b', 'c'))->toBe('a_b_c');
    });
});

describe('post type meta', function () {
    it('normalises a datetime-local value to UTC', function () {
        expect(BasePostType::sanitizeDateTime('2026-12-24T19:30'))->toBe('2026-12-24 19:30:00');
    });

    it('rejects junk and blanks', function (string $input) {
        expect(BasePostType::sanitizeDateTime($input))->toBe('');
    })->with(['not a date', '   ', '']);
});

describe('schemas', function () {
    it('derives the table name from the wpdb prefix', function () {
        expect((new TestSchema())->table())->toBe('wp_things');
    });

    it('namespaces the version option', function () {
        expect((new TestSchema())->versionOption())->toBe('larapress_schema_things');
    });

    it('records the version after installing', function () {
        $schema = new TestSchema();
        $schema->install();

        expect(get_option($schema->versionOption()))->toBe($schema->version());
    });

    it('skips a reinstall when the version matches', function () {
        $schema = new CountingSchema();
        $schema->install();
        $installs = $schema::$installs;

        $schema->ensure();

        expect($schema::$installs)->toBe($installs);
    });
});

describe('crons', function () {
    it('registers its handler on construction', function () {
        $GLOBALS['wp_actions_registered'] = [];

        new TestCron();

        expect($GLOBALS['wp_actions_registered'])->toContain('test_cron_hook');
    });

    it('defaults to a daily recurrence', function () {
        expect((new TestCron())->recurrence())->toBe('daily');
    });
});

describe('assets', function () {
    it('resolves a bare name to the conventional path', function () {
        config_set('app', ['resources_url' => 'https://example.test/resources', 'version' => '1.0.0']);

        [$handle, $options] = Asset::normalize(0, 'event-form', 'css');

        expect($handle)->toBe('event-form')
            ->and($options['src'])->toBe('https://example.test/resources/css/event-form.css')
            ->and($options['version'])->toBe('1.0.0')
            ->and($options['media'])->toBe('all');
    });

    it('lets an entry override the source, for a CDN', function () {
        [$handle, $options] = Asset::normalize('turnstile', [
            'src' => 'https://challenges.cloudflare.com/turnstile/v0/api.js',
            'version' => null,
        ], 'js');

        expect($handle)->toBe('turnstile')
            ->and($options['src'])->toBe('https://challenges.cloudflare.com/turnstile/v0/api.js')
            ->and($options['version'])->toBeNull()
            ->and($options['in_footer'])->toBeTrue();
    });

    it('carries dependencies through', function () {
        [, $options] = Asset::normalize('app', ['deps' => ['jquery']], 'js');

        expect($options['deps'])->toBe(['jquery']);
    });
});

describe('dead code', function () {
    it('no longer ships the unused interfaces', function (string $class) {
        expect(class_exists($class) || interface_exists($class))->toBeFalse();
    })->with([
        'Larapress\\Resources\\BaseCrud',
        'Larapress\\Contracts\\HasApi',
    ]);
});
