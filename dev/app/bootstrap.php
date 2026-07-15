<?php declare(strict_types=1);
/**
 * Application bootstrap — included once by public/index.php.
 * PSR-4 autoloader → configuration → database → helpers → session.
 */

// ── PSR-4 Autoloader: App\ namespace → app/ directory ──
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// ── Configuration ──
require_once __DIR__ . '/config/app.php';

// ── Core infrastructure ──
require_once __DIR__ . '/Core/Database.php';
require_once __DIR__ . '/Core/helpers.php';

// ── Start session (web only) ──
if (PHP_SAPI !== 'cli') {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        if (defined('APP_ENV') && APP_ENV === 'production') {
            ini_set('session.cookie_secure', '1');
        }
        ini_set('session.use_strict_mode', '1');
        session_cache_limiter('');
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

// ── Timezone ──
if (defined('TIMEZONE')) {
    date_default_timezone_set(TIMEZONE);
}

// ── Error reporting ──
if (defined('APP_DEBUG') && APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
