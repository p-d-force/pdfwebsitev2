<?php declare(strict_types=1);
namespace App\Controllers;

use App\Core\View;
use App\Core\Database;

class CaseController
{
    public function list(array $params = []): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = DEFAULT_PER_PAGE;
        $offset = ($page - 1) * $perPage;

        $where = "WHERE is_active = 1";
        $bindings = [];

        if (!empty($_GET['q'])) {
            $where .= " AND (title LIKE ? OR case_number LIKE ? OR summary LIKE ?)";
            $term = '%' . $_GET['q'] . '%';
            $bindings = [$term, $term, $term];
        }
        if (!empty($_GET['type'])) {
            $where .= " AND case_type = ?";
            $bindings[] = $_GET['type'];
        }
        if (!empty($_GET['status'])) {
            $where .= " AND status = ?";
            $bindings[] = $_GET['status'];
        }

        $total = (int)Database::fetchColumn("SELECT COUNT(*) FROM cases $where", $bindings);
        $allBindings = array_merge($bindings, [$perPage, $offset]);
        $cases = Database::fetchAll(
            "SELECT case_number, title, slug, case_type, status, filed_date, summary
             FROM cases $where ORDER BY filed_date DESC LIMIT ? OFFSET ?",
            $allBindings
        );

        View::render('case-list', [
            'page_title'      => 'Cases',
            'page_stylesheet' => 'cases',
            'cases'           => $cases,
            'pagination'      => paginate($total, $perPage),
        ]);
    }

    public function show(array $params = []): void
    {
        $slug = $params['slug'] ?? '';
        $case = Database::fetch(
            "SELECT c.*, o.org_name as org_name
             FROM cases c LEFT JOIN organizations o ON c.org_id = o.id
             WHERE (c.slug = ? OR c.case_number = ?) AND c.is_active = 1",
            [$slug, $slug]
        );

        if (!$case) {
            http_response_code(404);
            View::render('errors/404', ['page_title' => 'Case Not Found']);
            return;
        }

        $documents = Database::fetchAll(
            "SELECT d.id, d.title, d.file_name
             FROM case_document_links cdl
             JOIN documents d ON d.id = cdl.doc_id
             WHERE cdl.case_id = ?
             ORDER BY cdl.sort_order",
            [$case['id']]
        );

        View::render('case', [
            'page_title'      => $case['title'],
            'page_stylesheet' => 'cases',
            'page_description'=> truncate(strip_tags($case['summary'] ?? ''), 160),
            'case'            => $case,
            'documents'       => $documents,
        ]);
    }
}
