<?php declare(strict_types=1);
namespace App\Controllers;

use App\Core\View;
use App\Core\Database;

class ArticleController
{
    public function list(array $params = []): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = DEFAULT_PER_PAGE;
        $offset = ($page - 1) * $perPage;

        $total = (int)Database::fetchColumn("SELECT COUNT(*) FROM articles WHERE is_active = 1");
        $articles = Database::fetchAll(
            "SELECT title, slug, excerpt, published_date, article_type
             FROM articles WHERE is_active = 1
             ORDER BY published_date DESC LIMIT ? OFFSET ?",
            [$perPage, $offset]
        );

        $pagination = paginate($total, $perPage);

        View::render('article-list', [
            'page_title'      => 'Articles',
            'page_stylesheet' => 'articles',
            'articles'        => $articles,
            'pagination'      => $pagination,
        ]);
    }

    public function show(array $params = []): void
    {
        $slug = $params['slug'] ?? '';
        $article = Database::fetch("SELECT * FROM articles WHERE slug = ? AND is_active = 1", [$slug]);

        if (!$article) {
            http_response_code(404);
            View::render('errors/404', ['page_title' => 'Article Not Found']);
            return;
        }

        View::render('article', [
            'page_title'      => $article['title'],
            'page_stylesheet' => 'articles',
            'page_description'=> truncate(strip_tags($article['excerpt'] ?? ''), 160),
            'article'         => $article,
        ]);
    }
}
