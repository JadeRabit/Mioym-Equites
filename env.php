<?php
/**
 * env.php — minimal .env loader (no composer dependency).
 *
 * Loads KEY=VALUE pairs from the project-root `.env` file into
 * getenv() / $_ENV / $_SERVER exactly once per request.
 *
 * Rules:
 * - Real environment variables always win: existing values are never overwritten.
 * - Blank lines and lines starting with `#` are ignored.
 * - Optional `export ` prefix is supported.
 * - Single- and double-quoted values have their quotes stripped.
 * - Inline comments (` # ...`) are stripped only when outside quotes.
 * - No variable interpolation is performed (values are used literally).
 *
 * The file is optional: if no `.env` exists the app falls back to
 * server environment variables and then to built-in defaults.
 */

if (!function_exists('load_env')) {
    function load_env($path = null) {
        static $loaded = [];
        if ($path === null) {
            $path = __DIR__ . '/.env';
        }
        if (isset($loaded[$path])) {
            return;
        }
        $loaded[$path] = true;

        if (!is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            if (stripos($line, 'export ') === 0) {
                $line = trim(substr($line, 7));
            }

            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));

            if ($key === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
                continue;
            }

            $value = load_env_strip_value($value);

            // Real environment always wins over .env
            if (getenv($key) !== false || isset($_ENV[$key]) || isset($_SERVER[$key])) {
                continue;
            }

            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

if (!function_exists('load_env_strip_value')) {
    function load_env_strip_value($value) {
        $len = strlen($value);
        if ($len >= 2) {
            $first = $value[0];
            $last = $value[$len - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                return substr($value, 1, $len - 2);
            }
        }
        // Strip inline comment only when the `#` is outside quotes.
        $result = '';
        $inSingle = false;
        $inDouble = false;
        for ($i = 0; $i < $len; $i++) {
            $ch = $value[$i];
            if ($ch === "'" && !$inDouble) {
                $inSingle = !$inSingle;
                $result .= $ch;
            } elseif ($ch === '"' && !$inSingle) {
                $inDouble = !$inDouble;
                $result .= $ch;
            } elseif ($ch === '#' && !$inSingle && !$inDouble && ($i === 0 || $value[$i - 1] === ' ' || $value[$i - 1] === "\t")) {
                break;
            } else {
                $result .= $ch;
            }
        }
        return rtrim($result);
    }
}

if (!function_exists('env')) {
    /**
     * Get an environment value with a fallback default.
     * Empty-string values fall back to the default so that
     * `KEY=` in .env behaves as "not set".
     */
    function env($key, $default = null) {
        $value = getenv($key);
        if ($value === false && isset($_ENV[$key])) {
            $value = $_ENV[$key];
        }
        if ($value === false || $value === null || $value === '') {
            return $default;
        }
        return $value;
    }
}

if (!function_exists('env_raw')) {
    /**
     * Get an environment value where an empty string is a legitimate value
     * (e.g. DB_PASS for passwordless MySQL accounts). Falls back to the
     * default ONLY when the variable is not set at all.
     */
    function env_raw($key, $default = null) {
        $value = getenv($key);
        if ($value === false && isset($_ENV[$key])) {
            $value = $_ENV[$key];
        }
        if ($value === false || $value === null) {
            return $default;
        }
        return $value;
    }
}

// Auto-load the project-root .env when this file is required.
load_env(__DIR__ . '/.env');
