<?php declare(strict_types=1);
namespace App\Controllers;

use App\Core\View;
use App\Core\Database;

/**
 * Admin CRUD for PRS cases + data quality checks.
 */
class AdminPrsController extends AdminController
{
    /** GET /admin/prs — paginated table of all PRS cases */
    public function list(array $params = []): void
    {
        $user  = $this->requireAuth();
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 50;
        $offset  = ($page - 1) * $perPage;

        $total = (int)Database::fetchColumn("SELECT COUNT(*) FROM prs_cases");

        $cases = Database::fetchAll(
            "SELECT pc.*, o.org_name
             FROM prs_cases pc
             LEFT JOIN organizations o ON pc.org_id = o.id
             ORDER BY pc.filing_date DESC
             LIMIT ? OFFSET ?",
            [$perPage, $offset]
        );

        $qualityIssues = PrsController::qualityChecks();

        View::renderAdmin('prs/list', [
            'page_title'     => 'PRS Cases',
            'user'           => $user,
            'cases'          => $cases,
            'pagination'     => paginate($total, $perPage),
            'quality_issues' => $qualityIssues,
        ]);
    }

    /** GET+POST /admin/prs/new */
    public function create(array $params = []): void
    {
        $user = $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
            Database::beginTransaction();
            try {
                $prsNumber = trim($_POST['prs_number'] ?? '');
                if ($prsNumber === '') {
                    $prsNumber = 'PRS-' . date('Y') . '-' . str_pad((string)((int)Database::fetchColumn(
                        "SELECT COUNT(*) FROM prs_cases WHERE YEAR(created_at) = ?", [(int)date('Y')]
                    ) + 1), 4, '0', STR_PAD_LEFT);
                }

                $allegations = json_encode(array_filter(array_map('trim', explode("\n", $_POST['allegations'] ?? ''))));
                if ($allegations === '[""]') { $allegations = '[]'; }

                $caseId = (int)Database::insert(
                    "INSERT INTO prs_cases (prs_number, org_id, case_title, case_description,
                        filing_date, acceptance_date, investigation_start, findings_issued_date,
                        closure_date, current_status, resolution_type, complainant_type,
                        allegations, findings_summary, corrective_actions,
                        statutory_deadline, extended_deadline, actual_resolution_date, overdue_at_filing,
                        days_to_acceptance, days_to_findings, total_days_open)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $prsNumber,
                        !empty($_POST['org_id']) ? (int)$_POST['org_id'] : null,
                        $_POST['case_title'] ?: null,
                        $_POST['case_description'] ?: null,
                        $_POST['filing_date'] ?: null,
                        $_POST['acceptance_date'] ?: null,
                        $_POST['investigation_start'] ?: null,
                        $_POST['findings_issued_date'] ?: null,
                        $_POST['closure_date'] ?: null,
                        $_POST['current_status'] ?: 'filed',
                        $_POST['resolution_type'] ?: null,
                        $_POST['complainant_type'] ?: null,
                        $allegations,
                        $_POST['findings_summary'] ?: null,
                        $_POST['corrective_actions'] ?: null,
                        $_POST['statutory_deadline'] ?: null,
                        $_POST['extended_deadline'] ?: null,
                        $_POST['actual_resolution_date'] ?: null,
                        !empty($_POST['overdue_at_filing']) ? 1 : 0,
                        $_POST['days_to_acceptance'] !== '' ? (int)$_POST['days_to_acceptance'] : null,
                        $_POST['days_to_findings'] !== '' ? (int)$_POST['days_to_findings'] : null,
                        $_POST['total_days_open'] !== '' ? (int)$_POST['total_days_open'] : null,
                    ]
                );

                // Insert findings sub-form rows
                if (!empty($_POST['finding_category']) && is_array($_POST['finding_category'])) {
                    $fn = 1;
                    foreach ($_POST['finding_category'] as $i => $cat) {
                        if (empty($cat) && empty($_POST['finding_detail'][$i] ?? '')) {
                            continue;
                        }
                        Database::insert(
                            "INSERT INTO prs_findings (prs_case_id, finding_number, allegation_category,
                                allegation_subcategory, finding, finding_detail, cited_regulation,
                                corrective_action_ordered, corrective_action_status, corrective_action_deadline)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            [
                                $caseId, $fn++,
                                $cat ?: null,
                                $_POST['finding_subcategory'][$i] ?: null,
                                $_POST['finding_result'][$i] ?: null,
                                $_POST['finding_detail'][$i] ?: null,
                                $_POST['finding_regulation'][$i] ?: null,
                                $_POST['finding_ca'][$i] ?: null,
                                $_POST['finding_ca_status'][$i] ?: 'pending',
                                $_POST['finding_ca_deadline'][$i] ?: null,
                            ]
                        );
                    }
                }

                // Insert events sub-form rows
                if (!empty($_POST['event_type']) && is_array($_POST['event_type'])) {
                    foreach ($_POST['event_type'] as $i => $et) {
                        if (empty($et) || empty($_POST['event_date'][$i] ?? '')) {
                            continue;
                        }
                        Database::insert(
                            "INSERT INTO prs_events (prs_case_id, event_date, event_type, event_description, actor)
                             VALUES (?, ?, ?, ?, ?)",
                            [
                                $caseId,
                                $_POST['event_date'][$i],
                                $et,
                                $_POST['event_description'][$i] ?: null,
                                $_POST['event_actor'][$i] ?: null,
                            ]
                        );
                    }
                }

                Database::commit();
                $this->audit('create', 'prs_case', $caseId, $_POST['case_title'] ?? $prsNumber);
                redirect('/admin/prs');

            } catch (\Throwable $e) {
                Database::rollback();
                throw $e;
            }
        }

        $districts = Database::fetchAll(
            "SELECT id, org_code, org_name FROM organizations WHERE org_type = 'Public School District' AND is_active = 1 ORDER BY org_name"
        );

        View::renderAdmin('prs/form', [
            'page_title' => 'New PRS Case',
            'user'       => $user,
            'prs_case'   => null,
            'districts'  => $districts,
        ]);
    }

    /** GET+POST /admin/prs/{id}/edit */
    public function edit(array $params = []): void
    {
        $user = $this->requireAuth();
        $id   = (int)($params['id'] ?? 0);
        $case = Database::fetch("SELECT * FROM prs_cases WHERE id = ?", [$id]);

        if (!$case) {
            http_response_code(404);
            View::renderAdmin('errors/404', ['page_title' => 'Not Found']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
            Database::beginTransaction();
            try {
                $allegations = json_encode(array_filter(array_map('trim', explode("\n", $_POST['allegations'] ?? ''))));
                if ($allegations === '[""]') { $allegations = '[]'; }

                Database::execute(
                    "UPDATE prs_cases SET
                        prs_number = ?, org_id = ?, case_title = ?, case_description = ?,
                        filing_date = ?, acceptance_date = ?, investigation_start = ?, findings_issued_date = ?,
                        closure_date = ?, current_status = ?, resolution_type = ?, complainant_type = ?,
                        allegations = ?, findings_summary = ?, corrective_actions = ?,
                        statutory_deadline = ?, extended_deadline = ?, actual_resolution_date = ?,
                        overdue_at_filing = ?, days_to_acceptance = ?, days_to_findings = ?, total_days_open = ?,
                        updated_at = NOW()
                     WHERE id = ?",
                    [
                        $_POST['prs_number'] ?: $case['prs_number'],
                        !empty($_POST['org_id']) ? (int)$_POST['org_id'] : null,
                        $_POST['case_title'] ?: null,
                        $_POST['case_description'] ?: null,
                        $_POST['filing_date'] ?: null,
                        $_POST['acceptance_date'] ?: null,
                        $_POST['investigation_start'] ?: null,
                        $_POST['findings_issued_date'] ?: null,
                        $_POST['closure_date'] ?: null,
                        $_POST['current_status'] ?: 'filed',
                        $_POST['resolution_type'] ?: null,
                        $_POST['complainant_type'] ?: null,
                        $allegations,
                        $_POST['findings_summary'] ?: null,
                        $_POST['corrective_actions'] ?: null,
                        $_POST['statutory_deadline'] ?: null,
                        $_POST['extended_deadline'] ?: null,
                        $_POST['actual_resolution_date'] ?: null,
                        !empty($_POST['overdue_at_filing']) ? 1 : 0,
                        $_POST['days_to_acceptance'] !== '' ? (int)$_POST['days_to_acceptance'] : null,
                        $_POST['days_to_findings'] !== '' ? (int)$_POST['days_to_findings'] : null,
                        $_POST['total_days_open'] !== '' ? (int)$_POST['total_days_open'] : null,
                        $id,
                    ]
                );

                // Rebuild findings: delete existing, re-insert
                Database::execute("DELETE FROM prs_findings WHERE prs_case_id = ?", [$id]);

                if (!empty($_POST['finding_category']) && is_array($_POST['finding_category'])) {
                    $fn = 1;
                    foreach ($_POST['finding_category'] as $i => $cat) {
                        if (empty($cat) && empty($_POST['finding_detail'][$i] ?? '')) {
                            continue;
                        }
                        Database::insert(
                            "INSERT INTO prs_findings (prs_case_id, finding_number, allegation_category,
                                allegation_subcategory, finding, finding_detail, cited_regulation,
                                corrective_action_ordered, corrective_action_status, corrective_action_deadline)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            [
                                $id, $fn++,
                                $cat ?: null,
                                $_POST['finding_subcategory'][$i] ?: null,
                                $_POST['finding_result'][$i] ?: null,
                                $_POST['finding_detail'][$i] ?: null,
                                $_POST['finding_regulation'][$i] ?: null,
                                $_POST['finding_ca'][$i] ?: null,
                                $_POST['finding_ca_status'][$i] ?: 'pending',
                                $_POST['finding_ca_deadline'][$i] ?: null,
                            ]
                        );
                    }
                }

                // Rebuild events: delete existing, re-insert
                Database::execute("DELETE FROM prs_events WHERE prs_case_id = ?", [$id]);

                if (!empty($_POST['event_type']) && is_array($_POST['event_type'])) {
                    foreach ($_POST['event_type'] as $i => $et) {
                        if (empty($et) || empty($_POST['event_date'][$i] ?? '')) {
                            continue;
                        }
                        Database::insert(
                            "INSERT INTO prs_events (prs_case_id, event_date, event_type, event_description, actor)
                             VALUES (?, ?, ?, ?, ?)",
                            [
                                $id,
                                $_POST['event_date'][$i],
                                $et,
                                $_POST['event_description'][$i] ?: null,
                                $_POST['event_actor'][$i] ?: null,
                            ]
                        );
                    }
                }

                Database::commit();
                $this->audit('update', 'prs_case', $id, $_POST['case_title'] ?? $case['prs_number']);
                redirect('/admin/prs');

            } catch (\Throwable $e) {
                Database::rollback();
                throw $e;
            }
        }

        // Load for display
        $events = Database::fetchAll(
            "SELECT * FROM prs_events WHERE prs_case_id = ? ORDER BY event_date ASC, id ASC", [$id]
        );
        $findings = Database::fetchAll(
            "SELECT * FROM prs_findings WHERE prs_case_id = ? ORDER BY finding_number ASC", [$id]
        );
        $districts = Database::fetchAll(
            "SELECT id, org_code, org_name FROM organizations WHERE org_type = 'Public School District' AND is_active = 1 ORDER BY org_name"
        );

        View::renderAdmin('prs/form', [
            'page_title' => 'Edit PRS Case',
            'user'       => $user,
            'prs_case'   => $case,
            'events'     => $events,
            'findings'   => $findings,
            'districts'  => $districts,
        ]);
    }

    /** POST /admin/prs/{id}/delete */
    public function delete(array $params = []): void
    {
        $user = $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
            redirect('/admin/prs');
        }

        $id   = (int)($params['id'] ?? 0);
        $case = Database::fetch("SELECT prs_number, case_title FROM prs_cases WHERE id = ?", [$id]);

        if ($case) {
            // prs_events and prs_findings CASCADE on DELETE, but we clean up document_links
            Database::execute(
                "DELETE dl FROM document_links dl
                 WHERE dl.target_type = 'prs_case' AND dl.target_id = ?",
                [$id]
            );
            Database::execute("DELETE FROM prs_cases WHERE id = ?", [$id]);
            $this->audit('delete', 'prs_case', $id, $case['case_title'] ?? $case['prs_number']);
        }

        redirect('/admin/prs');
    }

    /** GET+POST /admin/prs/import — bulk import from prs_intakes */
    public function import(array $params = []): void
    {
        $user = $this->requireAuth();

        $imported = 0;
        $errors   = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
            $intakes = Database::fetchAll(
                "SELECT * FROM prs_intakes WHERE prs_number NOT IN (SELECT prs_number FROM prs_cases WHERE prs_number IS NOT NULL) ORDER BY intake_date DESC"
            );

            Database::beginTransaction();
            try {
                foreach ($intakes as $row) {
                    if (empty($row['prs_number'])) continue;

                    $prsNumber = trim($row['prs_number']);
                    // Map intake status to ENUM
                    $statusMap = [
                        'open'              => 'filed',
                        'investigating'     => 'investigating',
                        'findings_issued'   => 'findings',
                        'closed'            => 'closed',
                    ];
                    $status = $statusMap[$row['status'] ?? ''] ?? 'filed';

                    $caseId = (int)Database::insert(
                        "INSERT INTO prs_cases (prs_number, org_id, case_title, filing_date,
                            findings_issued_date, closure_date, current_status)
                         VALUES (?, ?, ?, ?, ?, ?, ?)",
                        [
                            $prsNumber,
                            $row['org_id'],
                            ($row['category'] ?? 'PRS Intake') . ' — ' . ($row['subcategory'] ?? ''),
                            $row['intake_date'],
                            $row['findings_date'],
                            $row['closure_date'] ?: ($row['status'] === 'closed' ? $row['findings_date'] : null),
                            $status,
                        ]
                    );

                    // Add a filing event
                    if (!empty($row['intake_date'])) {
                        Database::insert(
                            "INSERT INTO prs_events (prs_case_id, event_date, event_type, event_description)
                             VALUES (?, ?, 'filed', ?)",
                            [$caseId, $row['intake_date'], 'Imported from prs_intakes id=' . $row['id']]
                        );
                    }

                    $imported++;
                }
                Database::commit();
                $this->audit('import', 'prs_case', null, "Imported $imported prs_intakes");
            } catch (\Throwable $e) {
                Database::rollback();
                $errors[] = $e->getMessage();
            }
        }

        $remaining = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM prs_intakes WHERE prs_number NOT IN (SELECT prs_number FROM prs_cases WHERE prs_number IS NOT NULL)"
        );

        View::renderAdmin('prs/import', [
            'page_title' => 'Import PRS Cases',
            'user'       => $user,
            'imported'   => $imported,
            'errors'     => $errors,
            'remaining'  => $remaining,
        ]);
    }

    /** GET /admin/prs/quality — data quality dashboard */
    public function quality(array $params = []): void
    {
        $user    = $this->requireAuth();
        $issues  = PrsController::qualityChecks();

        View::renderAdmin('prs/quality', [
            'page_title' => 'PRS Data Quality',
            'user'       => $user,
            'issues'     => $issues,
        ]);
    }
}
