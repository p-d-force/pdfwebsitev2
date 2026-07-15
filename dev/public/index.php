<?php declare(strict_types=1);
/**
 * Parent Data Force — Front Controller
 * All requests route through here. Static assets bypass via .htaccess.
 */
require_once __DIR__ . '/../app/bootstrap.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

try {
    (new App\Router())->dispatch($method, $uri);
} catch (\Throwable $e) {
    http_response_code(500);
    error_log("500: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}");
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo "<h1>500 Internal Server Error</h1>";
        echo "<pre>" . h($e->getMessage()) . "\n\n" . h($e->getTraceAsString()) . "</pre>";
    } else {
        require __DIR__ . '/../app/views/errors/500.php';
    }
}
