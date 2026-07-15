<?php declare(strict_types=1);
namespace App\Controllers;

use App\Core\View;
use App\Core\Database;

class SearchController
{
    public function index(array $params = []): void
    {
        $query = trim($_GET['q'] ?? '');
        $results = [];
        $total = 0;

        if (strlen($query) > 0) {
            // Try FULLTEXT first
            try {
                $articleResults = Database::fetchAll(
                    "SELECT 'article' as type, title, slug, excerpt, '' as case_number, '' as summary
                     FROM articles WHERE is_active = 1 AND MATCH(title, excerpt, body) AGAINST(? IN BOOLEAN MODE)
                     LIMIT 20",
                    [$query]
                );
            } catch (\Exception $e) {
                // Fallback to LIKE
                $articleResults = Database::fetchAll(
                    "SELECT 'article' as type, title, slug, excerpt, '' as case_number, '' as summary
                     FROM articles WHERE is_active = 1 AND (title LIKE ? OR excerpt LIKE ?)
                     LIMIT 20",
                    ['%' . $query . '%', '%' . $query . '%']
                );
            }

            try {
                $caseResults = Database::fetchAll(
                    "SELECT 'case' as type, title, slug, '' as excerpt, case_number, summary
                     FROM cases WHERE is_active = 1 AND MATCH(title, summary, body) AGAINST(? IN BOOLEAN MODE)
                     LIMIT 20",
                    [$query]
                );
            } catch (\Exception $e) {
                $caseResults = Database::fetchAll(
                    "SELECT 'case' as type, title, slug, '' as excerpt, case_number, summary
                     FROM cases WHERE is_active = 1 AND (title LIKE ? OR summary LIKE ? OR case_number LIKE ?)
                     LIMIT 20",
                    ['%' . $query . '%', '%' . $query . '%', '%' . $query . '%']
                );
            }

            $results = array_merge($articleResults, $caseResults);
            $total = count($results);
        }

        View::render('search', [
            'page_title'      => 'Search',
            'page_stylesheet' => 'cases',
            'query'           => $query,
            'results'         => $results,
            'total'           => $total,
        ]);
    }
}
