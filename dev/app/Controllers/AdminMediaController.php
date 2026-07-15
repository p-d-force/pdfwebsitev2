<?php declare(strict_types=1);
namespace App\Controllers;

use App\Core\View;
use App\Core\Database;

class AdminMediaController extends AdminController
{
    public function list(array $params = []): void
    {
        $user = $this->requireAuth();
        $appearances = Database::fetchAll("SELECT * FROM media_appearances ORDER BY appearance_date DESC LIMIT 100");
        View::renderAdmin('media/list', [
            'page_title'   => 'Media Appearances',
            'user'         => $user,
            'appearances'  => $appearances,
        ]);
    }

    public function create(array $params = []): void
    {
        $user = $this->requireAuth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
            Database::insert("INSERT INTO media_appearances (title, venue, appearance_date, url, description) VALUES (?,?,?,?,?)", [$_POST['title'], $_POST['venue']?:null, $_POST['appearance_date']?:null, $_POST['url']?:null, $_POST['description']?:null]);
            $this->audit('create', 'media', (int)Database::getInstance()->lastInsertId(), $_POST['title']);
            redirect('/admin/media');
        }
        View::renderAdmin('media/form', ['page_title'=>'New Appearance','user'=>$user,'appearance'=>null]);
    }
    public function edit(array $params = []): void
    {
        $user = $this->requireAuth();
        $id = (int)($params['id'] ?? 0);
        $app = Database::fetch("SELECT * FROM media_appearances WHERE id = ?", [$id]);
        if (!$app) { http_response_code(404); View::renderAdmin('errors/404', ['page_title'=>'Not Found']); return; }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
            Database::execute("UPDATE media_appearances SET title=?, venue=?, appearance_date=?, url=?, description=?, updated_at=NOW() WHERE id=?", [$_POST['title'], $_POST['venue']?:null, $_POST['appearance_date']?:null, $_POST['url']?:null, $_POST['description']?:null, $id]);
            $this->audit('update', 'media', $id, $_POST['title']);
            redirect('/admin/media');
        }
        View::renderAdmin('media/form', ['page_title'=>'Edit Appearance','user'=>$user,'appearance'=>$app]);
    }
}
