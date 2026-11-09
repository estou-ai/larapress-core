<?php

use Larapress\Components\Table\Column;
use Larapress\Components\Table\Table;

afterEach(fn() => $_GET = []);

/**
 * Regression tests for issues found during the security review.
 */

describe('sort injection', function () {
    it('refuses a column that is not declared sortable', function (string $orderBy) {
        $query = new FakeQueryBuilder([]);

        Table::make()
            ->schema([Column::make('name')->sortable()])
            ->query($query)
            ->processData();

        $_GET = ['orderBy' => $orderBy, 'order' => 'asc'];

        $query2 = new FakeQueryBuilder([]);
        Table::make()
            ->schema([Column::make('name')->sortable()])
            ->query($query2)
            ->processData();

        expect($query2->reorders)->toBe([]);
    })->with([
        'id; DROP TABLE wp_posts',
        'name) UNION SELECT user_pass FROM wp_users --',
        'secret',
        '(SELECT 1)',
    ]);

    it('coerces the direction to asc or desc only', function (string $order) {
        $_GET = ['orderBy' => 'name', 'order' => $order];

        $query = new FakeQueryBuilder([]);
        Table::make()
            ->schema([Column::make('name')->sortable()])
            ->query($query)
            ->processData();

        expect($query->reorders[0][1])->toBeIn(['asc', 'desc']);
    })->with(['asc', 'desc', 'ASC; DROP TABLE x', 'nonsense', '']);
});

describe('paging bounds', function () {
    it('never produces a negative offset', function (string $paged) {
        $_GET = ['paged' => $paged];

        $query = new FakeQueryBuilder([], 100);
        Table::make()
            ->schema([Column::make('a')])
            ->query($query)
            ->perPage(10)
            ->processData();

        expect($query->offsetValue)->toBeGreaterThanOrEqual(0);
    })->with(['-1', '-999', '0', 'abc', '']);
});

describe('view rendering', function () {
    beforeEach(function () {
        $dir = BASE_PATH . '/views';

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        config_set('app', ['views' => $dir]);
    });

    it('refuses a view escaping the views directory', function (string $view) {
        file_put_contents(BASE_PATH . '/views/ok.php', 'fine');

        (new ProbeShortCode())->renderView($view);
    })->with([
        '/../../../../etc/passwd',
        '/../stubs.php',
        '../../composer.json',
    ])->throws(RuntimeException::class);

    it('renders a view inside the directory', function () {
        file_put_contents(BASE_PATH . '/views/greet.php', '<?= $name ?>');

        expect((new ProbeShortCode())->renderView('/greet.php', ['name' => 'Ana']))->toBe('Ana');
    });

    it('does not let a data key hijack the include path', function () {
        // Regression: the extraction loop ran in render()'s own scope, so a
        // data key named `view` overwrote the parameter and chose the file.
        file_put_contents(BASE_PATH . '/views/safe.php', 'safe output');

        $html = (new ProbeShortCode())->renderView('/safe.php', [
            'view' => '/../../../../etc/passwd',
            'path' => '/etc/passwd',
            'internalView' => true,
        ]);

        expect($html)->toBe('safe output');
    });

    it('can render the same view twice, as two shortcodes on a page would', function () {
        file_put_contents(BASE_PATH . '/views/twice.php', 'rendered');

        $probe = new ProbeShortCode();

        expect($probe->renderView('/twice.php'))->toBe('rendered')
            ->and($probe->renderView('/twice.php'))->toBe('rendered');
    });

    it('ignores a data key that is not a valid variable name', function () {
        file_put_contents(BASE_PATH . '/views/keys.php', 'ok');

        expect($probe = (new ProbeShortCode())->renderView('/keys.php', ['9bad' => 1, 'with-dash' => 2]))
            ->toBe('ok');
    });
});

describe('nonce scoping', function () {
    it('gives each resource and action its own nonce action', function () {
        config_set('app', ['slug' => 'my-plugin']);

        $a = (new ResourceAlpha())->nonceAction('delete');
        $b = (new ResourceBeta())->nonceAction('delete');
        $c = (new ResourceAlpha())->nonceAction('save');

        // A token minted for one resource/action must not validate another.
        expect($a)->not->toBe($b)
            ->and($a)->not->toBe($c)
            ->and($a)->toContain('my-plugin');
    });
});

describe('audit: capability default', function () {
    it('defaults to manage_options, not edit_posts', function () {
        // edit_posts is held by Contributors; it must never be the default
        // gate for admin pages and mutating actions.
        expect((new GuardedResource())->capability)->toBe('manage_options');
    });
});

describe('audit: snake_case action keys', function () {
    it('resolves the declared key behind a hook', function () {
        config_set('app', ['slug' => 'my-plugin']);

        $resource = new GuardedResource();
        $resource->declared = ['delete_all' => ['type' => 'admin_post', 'action' => 'no-op']];
        $resource->generate();

        $hook = 'admin_post_my-plugin_guarded_resource_delete_all';

        expect(GuardedResource::resolveActionKey($hook))->toBe('delete_all');
    });

    it('keys nonces and rules on the full declared key', function () {
        config_set('app', ['slug' => 'my-plugin']);

        $resource = new GuardedResource();
        $resource->declared = ['delete_all' => ['type' => 'admin_post', 'action' => 'no-op']];
        $resource->generate();

        // The last-segment split used to turn delete_all into "all", so the
        // form minted a nonce for the wrong action and rules() was looked up
        // under a name nobody declared.
        expect($resource->exposeNonceAction('delete_all'))->toContain('delete_all');
    });
});

describe('audit: guard transport', function () {
    beforeEach(function () {
        config_set('app', ['slug' => 'my-plugin']);
    });

    afterEach(function () {
        unset($_POST['_wpnonce'], $_GET['_wpnonce'], $_SERVER['REQUEST_METHOD']);
    });

    it('refuses a GET request to a default (POST) action', function () {
        $resource = new GuardedResource();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['_wpnonce'] = 'good-' . $resource->exposeNonceAction('save');

        $resource->authorize('save', ['type' => 'admin_post', 'action' => 'no-op']);
    })->throws(RuntimeException::class);

    it('refuses a POST nonce arriving in the query string', function () {
        $resource = new GuardedResource();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['_wpnonce'] = 'good-' . $resource->exposeNonceAction('save');
        $_POST['_wpnonce'] = '';

        $resource->authorize('save', ['type' => 'admin_post', 'action' => 'no-op']);
    })->throws(RuntimeException::class);

    it('accepts a POST with its nonce in the body', function () {
        $resource = new GuardedResource();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['_wpnonce'] = 'good-' . $resource->exposeNonceAction('save');

        $resource->authorize('save', ['type' => 'admin_post', 'action' => 'no-op']);

        expect(true)->toBeTrue();
    });

    it('accepts GET only when the action declares method get', function () {
        $resource = new GuardedResource();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['_wpnonce'] = 'good-' . $resource->exposeNonceAction('export');

        $resource->authorize('export', ['type' => 'admin_post', 'action' => 'no-op', 'method' => 'get']);

        expect(true)->toBeTrue();
    });
});

describe('audit: mail headers', function () {
    beforeEach(function () {
        $GLOBALS['wp_mail_sent'] = [];
    });

    it('strips CR/LF from the display name', function () {
        Larapress\Mail\Mailer::make()
            ->from("Bob\r\nBcc: attacker@evil.tld", 'no-reply@example.test')
            ->send('to@example.test', 's', 'b');

        $from = '';

        foreach ($GLOBALS['wp_mail_sent'][0]['headers'] as $header) {
            if (str_starts_with($header, 'From:')) {
                $from = $header;
            }
        }

        // Without CR/LF there is no header boundary, so the rest of the
        // payload stays literal text inside the From value.
        expect($from)->not->toContain("\r")
            ->and($from)->not->toContain("\n");
    });

    it('refuses an invalid recipient without calling wp_mail', function () {
        $sent = Larapress\Mail\Mailer::make()->send('not an address', 's', 'b');

        expect($sent)->toBeFalse()
            ->and($GLOBALS['wp_mail_sent'])->toBeEmpty();
    });

    it('refuses a recipient carrying a header-injection attempt', function () {
        // After sanitising, the payload collapses to an address with two @
        // signs, which is invalid — so the mail is not sent at all.
        $sent = Larapress\Mail\Mailer::make()->send("to@example.test\r\nBcc: attacker@evil.tld", 's', 'b');

        expect($sent)->toBeFalse()
            ->and($GLOBALS['wp_mail_sent'])->toBeEmpty();
    });
});

describe('audit: querystring keys', function () {
    it('encodes array keys drawn from the request uri', function () {
        $built = querystring('admin.php?"<script>=1', ['order' => 'asc']);

        expect($built)->not->toContain('<')
            ->and($built)->toContain('order=asc');
    });
});
