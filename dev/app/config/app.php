<?php declare(strict_types=1);
/**
 * Application configuration — environment-adaptive constants.
 * Port, database name, credentials all switch on production detection.
 */

// ── Load .env (simple parser — no composer dependency) ──
$env_file = dirname(__DIR__, 2) . '/.env';
if (file_exists($env_file)) {
    foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        // Strip surrounding quotes
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

// ── Environment detection ──
$is_production = (
    (isset($_SERVER['HTTP_HOST']) && str_contains($_SERVER['HTTP_HOST'], 'parentdataforce.com')) ||
    (getenv('APP_ENV') === 'production')
);

// ── Database ──
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', (int)(getenv('DB_PORT') ?: 3307));
define('DB_NAME', getenv('DB_NAME') ?: ($is_production ? 'pdf_db_production' : 'pdf_db'));
define('DB_USER', getenv('DB_USER') ?: ($is_production ? 'pdf_user_prod' : 'pdf_user'));
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: ($is_production ? '' : 'dev_password'));
define('DB_CHARSET', 'utf8mb4');

// ── Application ──
define('APP_ENV', $is_production ? 'production' : 'development');
define('APP_DEBUG', getenv('APP_DEBUG') ? (getenv('APP_DEBUG') === 'true') : !$is_production);
define('APP_SECRET', getenv('APP_SECRET') ?: 'change_this_to_a_random_string_at_least_32_chars_long');
define('SITE_URL', getenv('SITE_URL') ?: ($is_production ? 'https://www.parentdataforce.com' : 'http://localhost:8081'));
define('SITE_NAME', 'Parent Data Force');
define('SITE_TAGLINE', 'MAKING DATA MAKE SENSE');
define('TIMEZONE', 'America/New_York');
define('SITE_EMAIL', 'admin@parentdataforce.com');

// ── Pagination ──
define('DEFAULT_PER_PAGE', 25);
define('MAX_PER_PAGE', 100);

// ── Paths ──
define('ROOT_DIR', dirname(__DIR__, 2));
define('APP_DIR', __DIR__ . '/..');
define('PUBLIC_DIR', ROOT_DIR . '/public');
define('ASSETS_DIR', PUBLIC_DIR . '/assets');
define('ASSETS_URL', '/assets');
define('BACKEND_DIR', ROOT_DIR . '/backend');

// ── Admin session lifetime ──
define('ADMIN_SESSION_HOURS', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);
