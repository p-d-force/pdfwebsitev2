<?php declare(strict_types=1);
namespace App\Controllers;

use App\Core\View;
use App\Core\Database;

class AdminArticlesController extends AdminController
{
    /** GET /admin/articles */
    public function list(array $params = []): void
    {
        $user = $this->requireAuth();
        $articles = Database::fetchAll(
            "SELECT id, title, slug, article_type, is_featured, is_active, published_date
             FROM articles ORDER BY published_date DESC LIMIT 100"
        );
        View::renderAdmin('articles/list', [
            'page_title' => 'Articles',
            'user'       => $user,
            'articles'   => $articles,
        ]);
    }

    /** GET|POST /admin/articles/new */
    public function create(array $params = []): void
    {
        $user = $this->requireAuth();
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf()) { $error = 'Invalid request.'; }
            else {
                $slug = slugify($_POST['slug'] ?: $_POST['title']);
                Database::insert(
                    "INSERT INTO articles (title, slug, article_type, excerpt, body, is_featured, is_active, published_date)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $_POST['title'], $slug, $_POST['article_type'] ?: null,
                        $_POST['excerpt'] ?: null, $_POST['body'] ?: null,
                        (int)($_POST['is_featured'] ?? 0), (int)($_POST['is_active'] ?? 1),
                        $_POST['published_date'] ?: date('Y-m-d'),
                    ]
                );
                $this->audit('create', 'article', (int)Database::getInstance()->lastInsertId(), $_POST['title']);
                redirect('/admin/articles');
            }
        }

        View::renderAdmin('articles/form', [
            'page_title' => 'New Article', 'user' => $user, 'article' => null, 'error' => $error,
        ]);
    }

    /** GET|POST /admin/articles/{id}/edit */
    public function edit(array $params = []): void
    {
        $user = $this->requireAuth();
        $id = (int)($params['id'] ?? 0);
        $article = Database::fetch("SELECT * FROM articles WHERE id = ?", [$id]);
        if (!$article) { http_response_code(404); View::renderAdmin('errors/404', ['page_title' => 'Not Found']); return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf()) { $error = 'Invalid request.'; }
            else {
                Database::execute(
                    "UPDATE articles SET title=?, slug=?, article_type=?, excerpt=?, body=?, is_featured=?, is_active=?, published_date=?, updated_at=NOW() WHERE id=?",
                    [$_POST['title'], slugify($_POST['slug'] ?: $_POST['title']), $_POST['article_type'] ?: null,
                     $_POST['excerpt'] ?: null, $_POST['body'] ?: null,
                     (int)($_POST['is_featured'] ?? 0), (int)($_POST['is_active'] ?? 1),
                     $_POST['published_date'] ?: $article['published_date'], $id]
                );
                $this->audit('update', 'article', $id, $_POST['title']);
                redirect('/admin/articles');
            }
        }

        View::renderAdmin('articles/form', [
            'page_title' => 'Edit Article', 'user' => $user, 'article' => $article,
        ]);
    }
}
