<?php

if (!function_exists('env')) {
    /**
     * Get an environment variable or return default.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env(string $key, $default = null)
    {
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }
        return $default;
    }
}