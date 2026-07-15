<?php declare(strict_types=1);
namespace App\Controllers;

use App\Core\View;
use App\Core\Database;

class AdminOrganizationsController extends AdminController
{
    public function list(array $params = []): void
    {
        $user = $this->requireAuth();
        $orgs = Database::fetchAll(
            "SELECT id, org_code, org_name, org_type, town, is_active
             FROM organizations ORDER BY org_type, org_name LIMIT 200"
        );
        View::renderAdmin('organizations/list', [
            'page_title' => 'Organizations',
            'user'       => $user,
            'orgs'       => $orgs,
        ]);
    }

    public function edit(array $params = []): void
    {
        $user = $this->requireAuth();
        $id = (int)($params['id'] ?? 0);
        $org = Database::fetch("SELECT * FROM organizations WHERE id = ?", [$id]);
        if (!$org) { http_response_code(404); View::renderAdmin('errors/404', ['page_title'=>'Not Found']); return; }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
            Database::execute(
                "UPDATE organizations SET org_name=?, org_type=?, town=?, grade_span=?, is_active=?, updated_at=NOW() WHERE id=?",
                [$_POST['org_name'], $_POST['org_type'], $_POST['town']?:null, $_POST['grade_span']?:null, (int)($_POST['is_active']??1), $id]
            );
            $this->audit('update', 'organization', $id, $_POST['org_name']);
            redirect('/admin/organizations');
        }
        View::renderAdmin('organizations/form', ['page_title'=>'Edit Organization','user'=>$user,'org'=>$org]);
    }
}
