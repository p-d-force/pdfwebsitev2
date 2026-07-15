<?php declare(strict_types=1);
namespace App;

class Router
{
    public function dispatch(string $method, string $uri): void
    {
        $uri = rtrim($uri, '/') ?: '/';
        $routes = require __DIR__ . '/config/routes.php';

        foreach ($routes as $pattern => $route) {
            // Normalize: route can be [Controller::class, method] or [method(s), Controller::class, method]
            if (count($route) === 2) {
                [$class, $action] = $route;
                $allowedMethods = ['GET', 'POST'];
            } else {
                [$allowedMethods, $class, $action] = $route;
            }

            // Check HTTP method
            $methods = is_array($allowedMethods) ? $allowedMethods : [$allowedMethods];
            if (!in_array($method, $methods, true)) {
                continue;
            }

            $regex = '#^' . preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern) . '$#';
            if (preg_match($regex, $uri, $matches)) {
                $controller = new $class();
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $controller->$action($params);
                return;
            }
        }

        http_response_code(404);
        if (class_exists('App\\Core\\View')) {
            \App\Core\View::render('errors/404', ['page_title' => 'Page Not Found']);
        }
    }
}
