<?php
if (!function_exists('array_get')) {
    function array_get($array, $keys, $default = null)
    {
        $keys = explode('.', $keys);

        foreach ($keys as $key) {
            if (is_array($array) && array_key_exists($key, $array)) {
                $array = $array[$key];
            } else {
                return $default;
            }
        }

        return $array;
    }
}

if (!function_exists('public_path')) {
    function public_path($path): string
    {
        return config('app.resources_url') . $path;
    }
}

if (!function_exists('session')) {
    /**
     * Starts the session on first call and returns $_SESSION by reference.
     *
     * Call this instead of touching $_SESSION directly on the front end,
     * where the session is not started for you.
     */
    function &session(): array
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            // Hardened defaults rather than whatever php.ini says, which on
            // stock installs is httponly=0, secure=0: a session cookie
            // readable from JavaScript is a fixation and theft vector.
            if (function_exists('session_set_cookie_params')) {
                session_set_cookie_params([
                    'httponly' => true,
                    'samesite' => 'Lax',
                    'secure' => function_exists('is_ssl') && is_ssl(),
                ]);
            }

            session_start();
        }

        if (!isset($_SESSION)) {
            $_SESSION = [];
        }

        return $_SESSION;
    }
}

if (!function_exists('config')) {
    function config($key, $default = null)
    {
        global $configs;
        return array_get($configs, $key, $default);
    }
}

if (!function_exists('querystring')) {
    function querystring(string $url, array $arrValues = []): string
    {
        $url = str_replace("admin.php?", "", $url);
        $url = urldecode($url);
        parse_str($url, $output);
        $result = array_merge($output, $arrValues);
        $querystring = "admin.php?";
        if ($result && is_array($result)) {
            foreach ($result as $name => $value) {
                if (!str_ends_with($querystring, "?")) {
                    $querystring .= "&";
                }
                // Names come from REQUEST_URI via get_current_page(); encoding
                // them too keeps a crafted ?<script> key out of the built URL.
                $querystring .= urlencode("{$name}") . "=" . urlencode("{$value}");
            }
        }
        return $querystring;
    }
}

if (!function_exists('get_current_page')) {
    function get_current_page(): string
    {
        return basename($_SERVER['REQUEST_URI']);
    }
}

if (!function_exists('json_validate')){
    function json_validate($string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}

