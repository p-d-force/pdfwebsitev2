<?php declare(strict_types=1);
namespace App\Controllers;

use App\Core\View;
use App\Core\Database;

class AdminCasesController extends AdminController
{
    public function list(array $params = []): void
    {
        $user = $this->requireAuth();
        $cases = Database::fetchAll(
            "SELECT id, case_number, title, slug, case_type, status, is_active, filed_date
             FROM cases ORDER BY filed_date DESC LIMIT 100"
        );
        View::renderAdmin('cases/list', [
            'page_title' => 'Cases',
            'user'       => $user,
            'cases'      => $cases,
        ]);
    }

    public function create(array $params = []): void
    {
        $user = $this->requireAuth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
            $slug = slugify($_POST['slug'] ?: $_POST['case_number']);
            Database::insert(
                "INSERT INTO cases (case_number, title, slug, case_type, status, filed_date, summary, body, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)",
                [$_POST['case_number'], $_POST['title'], $slug, $_POST['case_type'] ?: null,
                 $_POST['status'] ?: 'open', $_POST['filed_date'] ?: date('Y-m-d'),
                 $_POST['summary'] ?: null, $_POST['body'] ?: null]
            );
            $this->audit('create', 'case', (int)Database::getInstance()->lastInsertId(), $_POST['title']);
            redirect('/admin/cases');
        }
        View::renderAdmin('cases/form', ['page_title'=>'New Case','user'=>$user,'legal_case'=>null]);
    }

    public function edit(array $params = []): void
    {
        $user = $this->requireAuth();
        $id = (int)($params['id'] ?? 0);
        $case = Database::fetch("SELECT * FROM cases WHERE id = ?", [$id]);
        if (!$case) { http_response_code(404); View::renderAdmin('errors/404', ['page_title'=>'Not Found']); return; }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
            Database::execute(
                "UPDATE cases SET case_number=?, title=?, slug=?, case_type=?, status=?, filed_date=?, summary=?, body=?, updated_at=NOW() WHERE id=?",
                [$_POST['case_number'], $_POST['title'], slugify($_POST['slug'] ?: $_POST['case_number']),
                 $_POST['case_type'] ?: null, $_POST['status'], $_POST['filed_date'] ?: null,
                 $_POST['summary'] ?: null, $_POST['body'] ?: null, $id]
            );
            $this->audit('update', 'case', $id, $_POST['title']);
            redirect('/admin/cases');
        }
        View::renderAdmin('cases/form', ['page_title'=>'Edit Case','user'=>$user,'legal_case'=>$case]);
    }
}
