<?php

namespace Larapress\Assets;

/**
 * Turns one css()/js() entry into enqueue arguments.
 *
 * Both the short form (a bare name) and the long form (a name plus options)
 * go through here, so the traits stay small and behave the same way.
 */
class Asset
{
    /**
     * @param int|string $key   Array key: the handle, or a list index.
     * @param mixed      $value The handle (short form) or options (long form).
     * @param string     $type  'css' or 'js'.
     *
     * @return array{0:string,1:array<string,mixed>}
     */
    public static function normalize($key, $value, string $type): array
    {
        // A list entry gives the handle as the value; a map entry as the key.
        $handle = is_int($key) ? (string) $value : (string) $key;
        $options = is_array($value) ? $value : [];

        $defaults = [
            'src' => public_path('/' . $type . '/' . $handle . '.' . $type),
            'deps' => [],
            'version' => config('app.version'),
        ];

        $defaults += $type === 'css'
            ? ['media' => 'all']
            : ['in_footer' => true];

        return [$handle, array_merge($defaults, $options)];
    }
}
