<?php declare(strict_types=1);
namespace App\Controllers;

use App\Core\Database;

class ApiController
{
    /** GET /api/data — JSON or CSV data for charts and export */
    public function data(array $params = []): void
    {
        $type   = $_GET['type'] ?? 'restraint-trends';
        $format = $_GET['format'] ?? 'json';

        if ($format === 'csv') {
            ob_start(function($buf) use ($type) {
                $data = json_decode($buf, true);
                if (!is_array($data) || empty($data)) return $buf;
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $type . '.csv"');
                $out = "\xEF\xBB\xBF"; // BOM for Excel
                $out .= implode(',', array_keys($data[0])) . "\r\n";
                foreach ($data as $row) {
                    $out .= '"' . implode('","', array_map(function($v) {
                        return str_replace('"', '""', (string)$v);
                    }, $row)) . '"' . "\r\n";
                }
                return $out;
            });
        }

        switch ($type) {
            // ── 1. restraint-trends (extended) ──
            case 'restraint-trends':
                $district = $_GET['district'] ?? '';
                $compare  = $_GET['compare'] ?? '';

                if (!empty($compare)) {
                    $codes    = explode(',', $compare);
                    $ph       = implode(',', array_fill(0, count($codes), '?'));
                    $rows     = Database::fetchAllCached(
                        "SELECT o.org_name, r.school_year,
                                SUM(r.total_restraints) AS restraints,
                                SUM(r.total_injuries) AS injuries
                         FROM restraint_data r JOIN organizations o ON r.org_id = o.id
                         WHERE o.org_code IN ($ph)
                         GROUP BY o.org_name, r.school_year ORDER BY r.school_year, o.org_name",
                        $codes,
                        300
                    );
                    json_response($rows);
                }

                if (!empty($district)) {
                    $rows = Database::fetchAllCached(
                        "SELECT r.school_year,
                                SUM(r.total_restraints) AS restraints,
                                SUM(r.total_injuries) AS injuries
                         FROM restraint_data r
                         JOIN organizations s ON r.org_id = s.id
                         JOIN organizations d ON s.parent_org_id = d.id
                         WHERE d.org_code = ?
                         GROUP BY r.school_year ORDER BY r.school_year",
                        [$district],
                        300
                    );
                } else {
                    $rows = Database::fetchAllCached(
                        "SELECT school_year, SUM(total_restraints) AS restraints, SUM(total_injuries) AS injuries
                         FROM restraint_data GROUP BY school_year ORDER BY school_year",
                        [],
                        300
                    );
                }
                json_response($rows);

            // ── 2. discipline-breakdown ──
            case 'discipline-breakdown':
                $district = $_GET['district'] ?? '';
                if (!empty($district)) {
                    $rows = Database::fetchAllCached(
                        "SELECT d.school_year,
                                ROUND(SUM(d.students * d.pct_in_school_susp / 100))    AS in_school_susp,
                                ROUND(SUM(d.students * d.pct_out_school_susp / 100))   AS out_school_susp,
                                ROUND(SUM(d.students * d.pct_expulsion / 100))         AS expulsion,
                                ROUND(SUM(d.students * d.pct_alt_setting / 100))       AS alt_setting,
                                ROUND(SUM(d.students * d.pct_emergency_removal / 100)) AS emergency_removal,
                                ROUND(SUM(d.students * d.pct_arrest / 100))            AS arrest,
                                ROUND(SUM(d.students * d.pct_law_enforce / 100))       AS law_enforce
                         FROM discipline_data d JOIN organizations o ON d.org_id = o.id
                         WHERE o.org_code = ? AND d.students > 0
                         GROUP BY d.school_year ORDER BY d.school_year",
                        [$district], 300
                    );
                } else {
                    $rows = Database::fetchAllCached(
                        "SELECT school_year,
                                ROUND(SUM(students * pct_in_school_susp / 100))    AS in_school_susp,
                                ROUND(SUM(students * pct_out_school_susp / 100))   AS out_school_susp,
                                ROUND(SUM(students * pct_expulsion / 100))         AS expulsion,
                                ROUND(SUM(students * pct_alt_setting / 100))       AS alt_setting,
                                ROUND(SUM(students * pct_emergency_removal / 100)) AS emergency_removal,
                                ROUND(SUM(students * pct_arrest / 100))            AS arrest,
                                ROUND(SUM(students * pct_law_enforce / 100))       AS law_enforce
                         FROM discipline_data WHERE students > 0 GROUP BY school_year ORDER BY school_year",
                        [], 300
                    );
                }
                json_response($rows);

            // ── 3. enrollment-demographics ──
            case 'enrollment-demographics':
                $district = $_GET['district'] ?? '';
                if (!empty($district)) {
                    $rows = Database::fetchAllCached(
                        "SELECT e.school_year,
                                e.sped_pct, e.el_pct, e.low_income_pct, e.high_needs_pct
                         FROM enrollment_data e JOIN organizations o ON e.org_id = o.id
                         WHERE o.org_code = ?
                         ORDER BY e.school_year",
                        [$district],
                        300
                    );
                } else {
                    $rows = Database::fetchAllCached(
                        "SELECT school_year,
                                AVG(sped_pct)       AS sped_pct,
                                AVG(el_pct)         AS el_pct,
                                AVG(low_income_pct) AS low_income_pct,
                                AVG(high_needs_pct) AS high_needs_pct
                         FROM enrollment_data GROUP BY school_year ORDER BY school_year",
                        [],
                        300
                    );
                }
                json_response($rows);

            // ── 4. attendance-trends ──
            case 'attendance-trends':
                $district = $_GET['district'] ?? '';
                if (!empty($district)) {
                    $rows = Database::fetchAllCached(
                        "SELECT a.school_year,
                                a.attendance_rate,
                                a.chronically_absent_10_pct AS chronic_absent_10,
                                a.chronically_absent_20_pct AS chronic_absent_20,
                                a.avg_absences
                         FROM attendance_data a JOIN organizations o ON a.org_id = o.id
                         WHERE o.org_code = ?
                         ORDER BY a.school_year",
                        [$district],
                        300
                    );
                } else {
                    $rows = Database::fetchAllCached(
                        "SELECT school_year,
                                AVG(attendance_rate)               AS attendance_rate,
                                AVG(chronically_absent_10_pct)     AS chronic_absent_10,
                                AVG(chronically_absent_20_pct)     AS chronic_absent_20,
                                AVG(avg_absences)                  AS avg_absences
                         FROM attendance_data GROUP BY school_year ORDER BY school_year",
                        [],
                        300
                    );
                }
                json_response($rows);

            // ── 5. sped-outcomes ──
            case 'sped-outcomes':
                $district = $_GET['district'] ?? '';
                if (!empty($district)) {
                    $rows = Database::fetchAllCached(
                        "SELECT s.school_year,
                                s.sped_grad_rate        AS grad_rate,
                                s.sped_dropout_rate     AS dropout_rate,
                                s.lre_full_incl_pct     AS inclusion_pct,
                                s.parent_involve_pct    AS parent_involve_pct,
                                s.post_school_engage_pct AS post_school_pct
                         FROM sped_results s JOIN organizations o ON s.org_id = o.id
                         WHERE o.org_code = ?
                         ORDER BY s.school_year",
                        [$district],
                        300
                    );
                } else {
                    $rows = Database::fetchAllCached(
                        "SELECT school_year,
                                AVG(sped_grad_rate)         AS grad_rate,
                                AVG(sped_dropout_rate)      AS dropout_rate,
                                AVG(lre_full_incl_pct)      AS inclusion_pct,
                                AVG(parent_involve_pct)     AS parent_involve_pct,
                                AVG(post_school_engage_pct) AS post_school_pct
                         FROM sped_results GROUP BY school_year ORDER BY school_year",
                        [],
                        300
                    );
                }
                json_response($rows);

            // ── 6. district-comparison (extended with &metrics=) ──
            case 'district-comparison':
                $codes   = $_GET['districts'] ?? '';
                $year    = $_GET['school_year'] ?? '';
                $metrics = $_GET['metrics'] ?? '';

                if (empty($codes) || empty($year)) {
                    json_response(['error' => 'districts and school_year required'], 400);
                }
                $codesArr     = explode(',', $codes);
                $placeholders = implode(',', array_fill(0, count($codesArr), '?'));
                $bindings     = array_merge([$year], $codesArr);

                // Original behavior: restraint data only
                if (empty($metrics)) {
                    $rows = Database::fetchAllCached(
                        "SELECT o.org_name, r.enrollment, r.total_restraints, r.total_injuries
                         FROM restraint_data r JOIN organizations o ON r.org_id = o.id
                         WHERE r.school_year = ? AND o.org_code IN ($placeholders)",
                        $bindings,
                        300
                    );
                    json_response($rows);
                }

                // Extended: multi-metric merge
                $metricsArr = explode(',', $metrics);
                $result = [];
                foreach ($codesArr as $i => $code) {
                    $result[$code] = ['org_code' => $code];
                }

                foreach ($metricsArr as $metric) {
                    switch ($metric) {
                        case 'restraint':
                            $rows = Database::fetchAllCached(
                                "SELECT d.org_code, d.org_name,
                                        SUM(r.total_restraints) AS total_restraints,
                                        SUM(r.total_injuries) AS total_injuries,
                                        SUM(r.enrollment) AS enrollment
                                 FROM restraint_data r
                                 JOIN organizations s ON r.org_id = s.id
                                 JOIN organizations d ON s.parent_org_id = d.id
                                 WHERE r.school_year = ? AND d.org_code IN ($placeholders)
                                 GROUP BY d.org_code, d.org_name",
                                $bindings, 300
                            );
                            foreach ($rows as $row) {
                                $result[$row['org_code']]['org_name'] = $row['org_name'];
                                $result[$row['org_code']]['restraints'] = (int) $row['total_restraints'];
                                $result[$row['org_code']]['injuries']   = (int) $row['total_injuries'];
                                $result[$row['org_code']]['enrollment'] = (int) $row['enrollment'];
                                $result[$row['org_code']]['restraint_rate'] = $row['enrollment']
                                    ? round($row['total_restraints'] / $row['enrollment'] * 100, 2) : null;
                            }
                        case 'discipline':
                            $rows = Database::fetchAllCached(
                                "SELECT o.org_code, o.org_name,
                                        d.pct_in_school_susp, d.pct_out_school_susp, d.pct_expulsion,
                                        d.pct_emergency_removal, d.pct_arrest, d.pct_law_enforce
                                 FROM discipline_data d JOIN organizations o ON d.org_id = o.id
                                 WHERE d.school_year = ? AND o.org_code IN ($placeholders)",
                                $bindings, 300
                            );
                            foreach ($rows as $row) {
                                $result[$row['org_code']]['org_name']           = $row['org_name'];
                                $result[$row['org_code']]['in_school_susp']    = (float) $row['pct_in_school_susp'];
                                $result[$row['org_code']]['out_school_susp']   = (float) $row['pct_out_school_susp'];
                                $result[$row['org_code']]['expulsion']         = (float) $row['pct_expulsion'];
                                $result[$row['org_code']]['emergency_removal'] = (float) $row['pct_emergency_removal'];
                                $result[$row['org_code']]['arrest']            = (float) $row['pct_arrest'];
                                $result[$row['org_code']]['law_enforce']       = (float) $row['pct_law_enforce'];
                            }
                            break;
                        case 'attendance':
                            $rows = Database::fetchAllCached(
                                "SELECT o.org_code, o.org_name, a.attendance_rate,
                                        a.chronically_absent_10_pct, a.chronically_absent_20_pct, a.avg_absences
                                 FROM attendance_data a JOIN organizations o ON a.org_id = o.id
                                 WHERE a.school_year = ? AND o.org_code IN ($placeholders)",
                                $bindings, 300
                            );
                            foreach ($rows as $row) {
                                $result[$row['org_code']]['org_name']          = $row['org_name'];
                                $result[$row['org_code']]['attendance_rate']  = (float) $row['attendance_rate'];
                                $result[$row['org_code']]['chronic_absent_10'] = (float) $row['chronically_absent_10_pct'];
                                $result[$row['org_code']]['chronic_absent_20'] = (float) $row['chronically_absent_20_pct'];
                                $result[$row['org_code']]['avg_absences']     = (float) $row['avg_absences'];
                            }
                            break;
                        case 'sped':
                            $rows = Database::fetchAllCached(
                                "SELECT o.org_code, o.org_name,
                                        s.sped_grad_rate, s.sped_dropout_rate,
                                        s.lre_full_incl_pct, s.post_school_engage_pct
                                 FROM sped_results s JOIN organizations o ON s.org_id = o.id
                                 WHERE s.school_year = ? AND o.org_code IN ($placeholders)",
                                $bindings, 300
                            );
                            foreach ($rows as $row) {
                                $result[$row['org_code']]['org_name']          = $row['org_name'];
                                $result[$row['org_code']]['sped_grad_rate']    = (float) $row['sped_grad_rate'];
                                $result[$row['org_code']]['sped_dropout_rate'] = (float) $row['sped_dropout_rate'];
                                $result[$row['org_code']]['inclusion_pct']     = (float) $row['lre_full_incl_pct'];
                                $result[$row['org_code']]['post_school_pct']   = (float) $row['post_school_engage_pct'];
                            }
                            break;
                    }
                }
                json_response(array_values($result));

            // ── 7. year-over-year ──
            case 'year-over-year':
                $metric   = $_GET['metric'] ?? '';
                $district = $_GET['district'] ?? '';

                if (empty($metric) || empty($district)) {
                    json_response(['error' => 'metric and district required'], 400);
                }

                $columnMap = [
                    'restraint_rate'   => 'r.total_restraints',
                    'discipline_rate'  => 'd.pct_out_school_susp',
                    'attendance_rate'  => 'a.attendance_rate',
                    'sped_grad'        => 's.sped_grad_rate',
                ];

                if (!isset($columnMap[$metric])) {
                    json_response(['error' => 'Unknown metric: ' . $metric], 400);
                }

                switch ($metric) {
                    case 'restraint_rate':
                        $raw = Database::fetchAllCached(
                            "SELECT r.school_year, r.total_restraints AS value
                             FROM restraint_data r JOIN organizations o ON r.org_id = o.id
                             WHERE o.org_code = ? ORDER BY r.school_year",
                            [$district],
                            300
                        );
                        break;
                    case 'discipline_rate':
                        $raw = Database::fetchAllCached(
                            "SELECT d.school_year, d.pct_out_school_susp AS value
                             FROM discipline_data d JOIN organizations o ON d.org_id = o.id
                             WHERE o.org_code = ? ORDER BY d.school_year",
                            [$district],
                            300
                        );
                        break;
                    case 'attendance_rate':
                        $raw = Database::fetchAllCached(
                            "SELECT a.school_year, a.attendance_rate AS value
                             FROM attendance_data a JOIN organizations o ON a.org_id = o.id
                             WHERE o.org_code = ? ORDER BY a.school_year",
                            [$district],
                            300
                        );
                        break;
                    case 'sped_grad':
                        $raw = Database::fetchAllCached(
                            "SELECT s.school_year, s.sped_grad_rate AS value
                             FROM sped_results s JOIN organizations o ON s.org_id = o.id
                             WHERE o.org_code = ? ORDER BY s.school_year",
                            [$district],
                            300
                        );
                        break;
                    default:
                        $raw = [];
                }

                $rows = [];
                $prev = null;
                foreach ($raw as $i => $row) {
                    $val = (float) $row['value'];
                    $entry = ['school_year' => $row['school_year'], 'value' => $val];

                    if ($i > 0 && $prev !== null && $prev != 0) {
                        $changeAbs   = round($val - $prev, 4);
                        $changePct   = round(($changeAbs / abs($prev)) * 100, 2);
                        $entry['change_abs'] = $changeAbs;
                        $entry['change_pct'] = $changePct;
                        $entry['direction']  = $changePct > 0.5 ? 'up' : ($changePct < -0.5 ? 'down' : 'flat');
                    } else {
                        $entry['change_abs'] = null;
                        $entry['change_pct'] = null;
                        $entry['direction']  = null;
                    }
                    $rows[] = $entry;
                    $prev = $val;
                }
                json_response($rows);

            // ── 8. prs-categories ──
            case 'prs-categories':
                $status = $_GET['status'] ?? '';
                $year   = $_GET['year'] ?? '';

                $where  = [];
                $params = [];

                if (!empty($status)) {
                    $where[]  = 'c.current_status = ?';
                    $params[] = $status;
                }
                if (!empty($year)) {
                    $where[]  = 'YEAR(c.filing_date) = ?';
                    $params[] = $year;
                }

                $whereClause = '';
                if (!empty($where)) {
                    $whereClause = 'WHERE ' . implode(' AND ', $where);
                }

                $rows = Database::fetchAllCached(
                    "SELECT COALESCE(JSON_UNQUOTE(JSON_EXTRACT(c.allegations, '$.category')), 'Uncategorized') AS category,
                            COUNT(*) AS count
                     FROM prs_cases c $whereClause
                     GROUP BY category ORDER BY count DESC",
                    $params,
                    300
                );
                json_response($rows);

            // ── 9. school-profile ──
            case 'school-profile':
                $orgCode = $_GET['org_code'] ?? '';
                if (empty($orgCode)) {
                    json_response(['error' => 'org_code required'], 400);
                }

                $school = Database::fetch(
                    "SELECT org_name, org_code, org_type, town, grade_span, parent_org_id
                     FROM organizations
                     WHERE org_code = ? AND is_active = 1",
                    [$orgCode]
                );

                if (!$school) {
                    json_response(['error' => 'School not found'], 404);
                }

                $district = null;
                if (!empty($school['parent_org_id'])) {
                    $district = Database::fetch(
                        "SELECT org_name, org_code FROM organizations WHERE id = ? AND is_active = 1",
                        [$school['parent_org_id']]
                    );
                }

                $restraint = Database::fetchAll(
                    "SELECT school_year, enrollment, students_restrained, total_restraints, total_injuries
                     FROM restraint_data
                     WHERE org_id = (SELECT id FROM organizations WHERE org_code = ?)
                     ORDER BY school_year",
                    [$orgCode]
                );

                $demographics = null;
                if (!empty($school['parent_org_id'])) {
                    $demographics = Database::fetch(
                        "SELECT sped_pct, el_pct, low_income_pct
                         FROM enrollment_data
                         WHERE org_id = ?
                         ORDER BY school_year DESC LIMIT 1",
                        [$school['parent_org_id']]
                    );
                }

                json_response([
                    'school' => [
                        'name'       => $school['org_name'],
                        'code'       => $school['org_code'],
                        'type'       => $school['org_type'],
                        'town'       => $school['town'],
                        'grade_span' => $school['grade_span'],
                    ],
                    'district' => $district ? [
                        'name' => $district['org_name'],
                        'code' => $district['org_code'],
                    ] : null,
                    'restraint'    => $restraint,
                    'demographics' => $demographics,
                ]);


            // ── 10. schools-explore — scatter plot data ──
            case 'schools-explore':
                $schoolYear = $_GET['school_year'] ?? '';
                $district  = $_GET['district'] ?? '';
                $gradeSpan = $_GET['grade_span'] ?? '';
                $minEnroll = (int)($_GET['min_enrollment'] ?? 0);
                $maxEnroll = (int)($_GET['max_enrollment'] ?? 99999);

                if (empty($schoolYear)) {
                    // Default to latest year
                    $schoolYear = Database::fetchColumn(
                        "SELECT MAX(school_year) FROM restraint_data"
                    );
                }

                $schoolTypes = "'Public School','Charter School','Collaborative School','Approved Special Education School'";
                $whereClauses = [
                    "s.org_type IN ({$schoolTypes})",
                    "s.is_active = 1",
                    "r.school_year = ?",
                    "r.enrollment > 0",
                ];
                $params = [$schoolYear];

                if (!empty($district)) {
                    $whereClauses[] = "d.org_code = ?";
                    $params[] = $district;
                }
                if (!empty($gradeSpan)) {
                    $whereClauses[] = "s.grade_span = ?";
                    $params[] = $gradeSpan;
                }
                if ($minEnroll > 0) {
                    $whereClauses[] = "r.enrollment >= ?";
                    $params[] = $minEnroll;
                }
                if ($maxEnroll < 99999) {
                    $whereClauses[] = "r.enrollment <= ?";
                    $params[] = $maxEnroll;
                }

                $where = implode(' AND ', $whereClauses);

                $rows = Database::fetchAllCached(
                    "SELECT s.org_name, s.org_code, s.grade_span, s.town,
                            d.org_name AS district_name, d.org_code AS district_code,
                            r.enrollment, r.students_restrained, r.total_restraints, r.total_injuries,
                            e.sped_pct, e.low_income_pct, e.el_pct,
                            disc.pct_out_school_susp, disc.pct_in_school_susp,
                            a.attendance_rate, a.chronically_absent_10_pct
                     FROM organizations s
                     JOIN restraint_data r ON r.org_id = s.id
                     JOIN organizations d ON s.parent_org_id = d.id
                     LEFT JOIN enrollment_data e ON e.org_id = d.id AND e.school_year = r.school_year
                     LEFT JOIN discipline_data disc ON disc.org_id = d.id AND disc.school_year = r.school_year
                     LEFT JOIN attendance_data a ON a.org_id = d.id AND a.school_year = r.school_year
                     WHERE {$where}
                     ORDER BY d.org_name, s.org_name",
                    $params,
                    120
                );

                // Available school years for slider
                $years = Database::fetchAllCached(
                    "SELECT DISTINCT school_year FROM restraint_data ORDER BY school_year",
                    [],
                    300
                );

                // Districts for filter
                $districts = Database::fetchAllCached(
                    "SELECT DISTINCT d.org_name, d.org_code
                     FROM organizations d
                     WHERE d.org_type = 'Public School District' AND d.is_active = 1
                     ORDER BY d.org_name",
                    [],
                    300
                );

                json_response([
                    'schools'   => $rows,
                    'years'     => array_column($years, 'school_year'),
                    'districts' => $districts,
                ]);

            default:
                json_response(['error' => 'Unknown data type'], 400);
        }
    }

    /** GET /api/cases */
    public function cases(array $params = []): void
    {
        $rows = Database::fetchAll(
            "SELECT case_number, title, slug, case_type, status, filed_date
             FROM cases WHERE is_active = 1 ORDER BY filed_date DESC LIMIT 100"
        );
        json_response($rows);
    }

    /** GET /api/articles */
    public function articles(array $params = []): void
    {
        $rows = Database::fetchAll(
            "SELECT title, slug, excerpt, article_type, published_date
             FROM articles WHERE is_active = 1 ORDER BY published_date DESC LIMIT 100"
        );
        json_response($rows);
    }

    /** GET /api/search — autocomplete suggestions */
    public function search(array $params = []): void
    {
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) { json_response([]); }

        // School search
        if (($_GET['type'] ?? '') === 'school') {
            $schools = Database::fetchAll(
                "SELECT org_name AS title, LOWER(org_code) AS slug, 'school' AS type
                 FROM organizations
                 WHERE org_type IN ('Public School','Charter School','Collaborative School','Approved Special Education School')
                   AND is_active = 1 AND org_name LIKE ? LIMIT 8",
                ['%' . $q . '%']
            );
            json_response($schools);
        }

        // PRS search
        if (($_GET['type'] ?? '') === 'prs') {
            $prsTitles = Database::fetchAll(
                "SELECT case_title AS title, prs_number AS slug, 'prs' AS type
                 FROM prs_cases
                 WHERE case_title LIKE ? LIMIT 8",
                ['%' . $q . '%']
            );
            $prsFindings = Database::fetchAll(
                "SELECT pf.finding_detail AS title, pc.prs_number AS slug, 'prs_finding' AS type
                 FROM prs_findings pf
                 JOIN prs_cases pc ON pf.prs_case_id = pc.id
                 WHERE pf.finding_detail LIKE ? LIMIT 5",
                ['%' . $q . '%']
            );
            json_response(array_merge($prsTitles, $prsFindings));
        }

        $articles = Database::fetchAll(
            "SELECT title, slug, 'article' as type FROM articles
             WHERE is_active = 1 AND title LIKE ? LIMIT 5",
            ['%' . $q . '%']
        );
        $cases = Database::fetchAll(
            "SELECT title, slug, case_number, 'case' as type FROM cases
             WHERE is_active = 1 AND title LIKE ? LIMIT 5",
            ['%' . $q . '%']
        );
        json_response(array_merge($articles, $cases));
    }

    /** POST /api/submit */
    public function submit(array $params = []): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['error' => 'Method not allowed'], 405);
        }

        if (!verify_csrf()) {
            json_response(['error' => 'Invalid CSRF token'], 403);
        }

        $title = trim($_POST['title'] ?? '');
        $body = trim($_POST['body'] ?? '');

        if (empty($title) || empty($body)) {
            json_response(['error' => 'Title and body are required'], 400);
        }

        Database::insert(
            "INSERT INTO submissions (submitter_name, submitter_email, submitter_org, title, body, submission_type)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                trim($_POST['submitter_name'] ?? '') ?: null,
                trim($_POST['submitter_email'] ?? '') ?: null,
                trim($_POST['submitter_org'] ?? '') ?: null,
                $title,
                $body,
                $_POST['submission_type'] ?? 'tip',
            ]
        );

        json_response(['success' => true, 'message' => 'Submission received. Thank you.']);
    }

    /** POST /api/subscribe */
    public function subscribe(array $params = []): void
    {
        json_response(['success' => true, 'message' => 'Subscription feature coming soon.']);
    }
}
