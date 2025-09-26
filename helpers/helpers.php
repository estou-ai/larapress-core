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
        return helpers . phpconfig('app.resources_url') . $path;
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
                $querystring .= "{$name}" . "=" . urlencode("{$value}");
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

if(!function_exists('get_from_cache')){
    function get_from_cache(string $key, callable $callback)
    {
        if (file_exists(__DIR__.'/../../../bootstrap/cache/'.$key.'-'.date('Y_m_d').'.php')) {
            return require __DIR__.'/../../../bootstrap/cache/'.$key.'-'.date('Y_m_d').'.php';
        }

        $value = $callback();
        file_put_contents(__DIR__.'/../../../bootstrap/cache/'.$key.'-'.date('Y_m_d').'.php', '<?php return '.var_export($value, true).';');
        return $value;
    }
}