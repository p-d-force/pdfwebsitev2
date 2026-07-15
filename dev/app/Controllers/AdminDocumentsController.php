<?php declare(strict_types=1);
namespace App\Controllers;

use App\Core\View;
use App\Core\Database;

class AdminDocumentsController extends AdminController
{
    public function list(array $params = []): void
    {
        $user = $this->requireAuth();
        $docs = Database::fetchAll(
            "SELECT d.id, d.title, d.file_name, d.doc_family, d.source_system, w.is_public, w.status
             FROM documents d LEFT JOIN document_workflow w ON w.doc_id = d.id
             ORDER BY d.created_at DESC LIMIT 200"
        );
        View::renderAdmin('documents/list', [
            'page_title' => 'Documents',
            'user'       => $user,
            'docs'       => $docs,
        ]);
    }

    public function edit(array $params = []): void
    {
        $user = $this->requireAuth();
        $id = (int)($params['id'] ?? 0);
        $doc = Database::fetch("SELECT d.*, w.status as wf_status, w.is_public FROM documents d LEFT JOIN document_workflow w ON w.doc_id = d.id WHERE d.id = ?", [$id]);
        if (!$doc) { http_response_code(404); View::renderAdmin('errors/404', ['page_title'=>'Not Found']); return; }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
            Database::execute("UPDATE documents SET title=?, doc_family=?, source_system=?, description=?, updated_at=NOW() WHERE id=?", [$_POST['title'], $_POST['doc_family']?:null, $_POST['source_system']?:null, $_POST['description']?:null, $id]);
            $pub = (int)($_POST['is_public'] ?? 0);
            Database::execute("INSERT INTO document_workflow (doc_id, is_public) VALUES (?,?) ON DUPLICATE KEY UPDATE is_public=?", [$id, $pub, $pub]);
            $this->audit('update', 'document', $id, $_POST['title']);
            redirect('/admin/documents');
        }
        View::renderAdmin('documents/form', ['page_title'=>'Edit Document','user'=>$user,'doc'=>$doc]);
    }
}
