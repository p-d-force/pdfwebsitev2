<?php declare(strict_types=1);
namespace App\Core;

/**
 * View renderer — extracts data and requires the template.
 * Templates live in app/views/ and use $variables directly.
 */
class View
{
    public static function render(string $template, array $data = []): void
    {
        $view = APP_DIR . '/views/' . $template . '.php';
        if (!file_exists($view)) {
            throw new \RuntimeException("View not found: $template");
        }
        extract($data, EXTR_SKIP);
        $page_type ??= 'website';
        $page_under_development ??= false;
        require APP_DIR . '/Components/Layout.php';
    }

    public static function renderAdmin(string $template, array $data = []): void
    {
        $view = APP_DIR . '/views/admin/' . $template . '.php';
        if (!file_exists($view)) {
            throw new \RuntimeException("Admin view not found: $template");
        }
        extract($data, EXTR_SKIP);
        $page_type ??= 'website';
        require APP_DIR . '/views/admin/_layout.php';
    }
}
