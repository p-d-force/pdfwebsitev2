<?php declare(strict_types=1);
namespace App\Controllers;

use App\Core\View;
use App\Core\Database;

class AdminUpdatesController extends AdminController
{
    public function list(array $params = []): void
    {
        $user = $this->requireAuth();
        $updates = Database::fetchAll("SELECT * FROM updates ORDER BY published_date DESC LIMIT 100");
        View::renderAdmin('updates/list', [
            'page_title' => 'Updates',
            'user'       => $user,
            'updates'    => $updates,
        ]);
    }

    public function create(array $params = []): void
    {
        $user = $this->requireAuth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
            Database::insert("INSERT INTO updates (title, update_type, excerpt, published_date, is_active) VALUES (?,?,?,NOW(),1)", [$_POST['title'], $_POST['update_type']?:null, $_POST['excerpt']?:null]);
            $this->audit('create', 'update', (int)Database::getInstance()->lastInsertId(), $_POST['title']);
            redirect('/admin/updates');
        }
        View::renderAdmin('updates/form', ['page_title'=>'New Update','user'=>$user,'update'=>null]);
    }
    public function edit(array $params = []): void
    {
        $user = $this->requireAuth();
        $id = (int)($params['id'] ?? 0);
        $update = Database::fetch("SELECT * FROM updates WHERE id = ?", [$id]);
        if (!$update) { http_response_code(404); View::renderAdmin('errors/404', ['page_title'=>'Not Found']); return; }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
            Database::execute("UPDATE updates SET title=?, update_type=?, excerpt=?, updated_at=NOW() WHERE id=?", [$_POST['title'], $_POST['update_type']?:null, $_POST['excerpt']?:null, $id]);
            $this->audit('update', 'update', $id, $_POST['title']);
            redirect('/admin/updates');
        }
        View::renderAdmin('updates/form', ['page_title'=>'Edit Update','user'=>$user,'update'=>$update]);
    }
}
