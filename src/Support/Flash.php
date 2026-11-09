<?php

namespace Larapress\Support;

/**
 * One-shot messages that survive a redirect without a PHP session.
 *
 * A session cookie suppresses full-page caching and does not cross servers,
 * so messages ride a short-lived transient instead, named by a token carried
 * in the redirect URL. Reading consumes it.
 */
class Flash
{
    public const PARAM = 'lp_flash';

    private const TTL = 120;

    /**
     * Stores a payload and returns the URL to redirect to.
     *
     * @param array<string,mixed> $payload
     */
    public static function to(string $url, array $payload): string
    {
        $token = bin2hex(random_bytes(8));

        set_transient(self::key($token), $payload, self::TTL);

        return add_query_arg(self::PARAM, $token, remove_query_arg(self::PARAM, $url));
    }

    /**
     * Reads and clears the payload named by the current request, if any.
     *
     * @return array<string,mixed>|null
     */
    public static function take(): ?array
    {
        $payload = self::peek();

        if ($payload !== null) {
            self::forget();
        }

        return $payload;
    }

    /**
     * Reads the payload without consuming it, for when the caller must first
     * decide whether the message is addressed to it — two forms on one page
     * would otherwise have the first one swallow the second one's message.
     *
     * @return array<string,mixed>|null
     */
    public static function peek(): ?array
    {
        $token = self::token();

        if ($token === '') {
            return null;
        }

        $payload = get_transient(self::key($token));

        return is_array($payload) ? $payload : null;
    }

    public static function forget(): void
    {
        $token = self::token();

        if ($token !== '') {
            delete_transient(self::key($token));
        }
    }

    private static function token(): string
    {
        return isset($_GET[self::PARAM]) ? sanitize_key($_GET[self::PARAM]) : '';
    }

    /**
     * Lowercase hex only: the reader runs the token through sanitize_key(),
     * which would lowercase a mixed-case token and miss the transient.
     */
    private static function key(string $token): string
    {
        return 'lp_flash_' . $token;
    }
}
