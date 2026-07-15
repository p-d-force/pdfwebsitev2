<?php declare(strict_types=1);
namespace App\Controllers;

use App\Core\View;
use App\Core\Database;

class AdminSubmissionsController extends AdminController
{
    public function list(array $params = []): void
    {
        $user = $this->requireAuth();
        $submissions = Database::fetchAll(
            "SELECT * FROM submissions ORDER BY submitted_at DESC LIMIT 100"
        );
        View::renderAdmin('submissions/list', [
            'page_title' => 'Submissions',
            'user'       => $user,
            'submissions'=> $submissions,
        ]);
    }

    public function review(array $params = []): void
    {
        $user = $this->requireAuth();
        $id = (int)($params['id'] ?? 0);
        $sub = Database::fetch("SELECT * FROM submissions WHERE id = ?", [$id]);
        if (!$sub) { http_response_code(404); View::renderAdmin('errors/404', ['page_title'=>'Not Found']); return; }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
            $status = ($_POST['action'] ?? '') === 'accept' ? 'accepted' : 'rejected';
            Database::execute("UPDATE submissions SET status=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?", [$status, $user['admin_user_id'], $id]);
            $this->audit($status, 'submission', $id, $sub['title']);
            redirect('/admin/submissions');
        }
        View::renderAdmin('submissions/review', ['page_title'=>'Review Submission','user'=>$user,'submission'=>$sub]);
    }
}
