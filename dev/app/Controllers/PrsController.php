<?php declare(strict_types=1);
namespace App\Controllers;

use App\Core\View;
use App\Core\Database;
use App\Components\Chart;

/**
 * PRS Tracker — public pages, APIs, and analytics.
 */
class PrsController
{
    // ──────────────────────────────────────────────
    //  PAGE ROUTES
    // ──────────────────────────────────────────────

    /** GET /prs — paginated list with filters */
    public function list(array $params = []): void
    {
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 25)));
        $offset  = ($page - 1) * $perPage;

        $where   = 'WHERE 1=1';
        $bindings = [];

        if (!empty($_GET['status'])) {
            $where .= ' AND pc.current_status = ?';
            $bindings[] = $_GET['status'];
        }
        if (!empty($_GET['year'])) {
            $where .= ' AND YEAR(pc.filing_date) = ?';
            $bindings[] = (int)$_GET['year'];
        }
        if (!empty($_GET['district'])) {
            $where .= ' AND o.org_code = ?';
            $bindings[] = $_GET['district'];
        }
        if (!empty($_GET['q'])) {
            $where .= ' AND (pc.prs_number LIKE ? OR pc.case_title LIKE ?)';
            $kw = '%' . $_GET['q'] . '%';
            $bindings[] = $kw;
            $bindings[] = $kw;
        }

        $total = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM prs_cases pc LEFT JOIN organizations o ON pc.org_id = o.id $where",
            $bindings
        );

        $queryBindings = array_merge($bindings, [$perPage, $offset]);
        $cases = Database::fetchAll(
            "SELECT pc.id, pc.prs_number, pc.case_title, pc.current_status, pc.filing_date,
                    pc.closure_date, pc.total_days_open,
                    o.org_name as district_name, o.org_code
             FROM prs_cases pc
             LEFT JOIN organizations o ON pc.org_id = o.id
             $where
             ORDER BY pc.filing_date DESC
             LIMIT ? OFFSET ?",
            $queryBindings
        );

        $years = Database::fetchAll(
            "SELECT DISTINCT YEAR(filing_date) as yr FROM prs_cases WHERE filing_date IS NOT NULL ORDER BY yr DESC"
        );

        View::render('prs/list', [
            'page_title'       => 'PRS Case Tracker',
            'page_description' => 'Track Problem Resolution System (PRS) complaints across Massachusetts school districts.',
            'page_stylesheet'  => 'data',
            'cases'            => $cases,
            'years'            => $years,
            'pagination'       => paginate($total, $perPage),
            'filters'          => $_GET,
        ]);
    }

    /** GET /prs/{prs_number} — full case detail */
    public function show(array $params = []): void
    {
        $prsNumber = $params['prs_number'] ?? '';

        // ── Case with org join + computed days_open ──
        $case = Database::fetch(
            "SELECT pc.*, o.org_name, o.org_code,
                    DATEDIFF(COALESCE(pc.closure_date, CURDATE()), pc.filing_date) AS days_open
             FROM prs_cases pc
             LEFT JOIN organizations o ON pc.org_id = o.id
             WHERE pc.prs_number = ?",
            [$prsNumber]
        );

        if (!$case) {
            http_response_code(404);
            View::render('errors/404', ['page_title' => 'Case Not Found']);
            return;
        }

        // ── Events ──
        $events = Database::fetchAll(
            "SELECT * FROM prs_events WHERE prs_case_id = ? ORDER BY event_date ASC, id ASC",
            [(int)$case['id']]
        );

        // ── Findings ──
        $findings = Database::fetchAll(
            "SELECT * FROM prs_findings WHERE prs_case_id = ? ORDER BY finding_number ASC",
            [(int)$case['id']]
        );

        // ── Documents linked to this case ──
        $documents = Database::fetchAll(
            "SELECT d.id, d.title, d.file_name, d.file_url, d.file_mime, d.document_date,
                    dl.link_type
             FROM documents d
             JOIN document_links dl ON d.id = dl.doc_id
             WHERE dl.target_type = 'prs_case' AND dl.target_id = ?
             ORDER BY d.document_date DESC",
            [(int)$case['id']]
        );

        // ── Group documents by link_type ──
        $documentsByType = [];
        foreach ($documents as $d) {
            $type = $d['link_type'] ?: 'other';
            $documentsByType[$type][] = $d;
        }

        // ── Decode allegations JSON ──
        $allegations = [];
        if (!empty($case['allegations'])) {
            $decoded = json_decode($case['allegations'], true);
            $allegations = is_array($decoded) ? $decoded : [];
        }

        // ── Related cases: same district, filed within 180 days ──
        $relatedCases = [];
        if (!empty($case['org_id']) && !empty($case['filing_date'])) {
            $relatedCases = Database::fetchAll(
                "SELECT pc.prs_number, pc.case_title, pc.current_status, pc.filing_date
                 FROM prs_cases pc
                 WHERE pc.org_id = ? AND pc.id != ? AND pc.filing_date IS NOT NULL
                   AND ABS(DATEDIFF(pc.filing_date, ?)) <= 180
                 ORDER BY pc.filing_date DESC
                 LIMIT 6",
                [(int)$case['org_id'], (int)$case['id'], $case['filing_date']]
            );
        }

        // ── Deadline computation ──
        $deadline = $case['statutory_deadline'] ?? null;
        if (!$deadline && !empty($case['filing_date'])) {
            $deadline = date('Y-m-d', strtotime($case['filing_date'] . ' +60 days'));
        }
        $deadlineTs = $deadline ? strtotime($deadline) : 0;
        $nowTs = time();
        $daysUntilDeadline = $deadlineTs ? (int)ceil(($deadlineTs - $nowTs) / 86400) : null;
        $isOverdue = $daysUntilDeadline !== null && $daysUntilDeadline < 0 && $case['current_status'] !== 'closed';

        // ── District context: restraint rate for filing year ──
        $districtContext = null;
        if (!empty($case['org_id']) && !empty($case['filing_date'])) {
            $filingYear = date('Y', strtotime($case['filing_date']));
            // School year format like "2024-2025"
            $schoolYear = $filingYear . '-' . ($filingYear + 1);
            // Get district restraint rate: aggregate from schools that belong to this district org
            $districtContext = Database::fetch(
                "SELECT
                    COALESCE(SUM(rd.total_restraints), 0) AS total_restraints,
                    COALESCE(SUM(rd.enrollment), 0) AS total_enrollment,
                    CASE WHEN COALESCE(SUM(rd.enrollment), 0) > 0
                         THEN ROUND((SUM(rd.total_restraints) / SUM(rd.enrollment)) * 100, 1)
                         ELSE NULL END AS rate_per_100
                 FROM restraint_data rd
                 JOIN organizations sch ON rd.org_id = sch.id
                 WHERE sch.parent_org_id = ? AND rd.school_year = ?",
                [(int)$case['org_id'], $schoolYear]
            );
            // State average for same year
            if ($districtContext) {
                $stateAvg = Database::fetch(
                    "SELECT
                        CASE WHEN COALESCE(SUM(rd.enrollment), 0) > 0
                             THEN ROUND((SUM(rd.total_restraints) / SUM(rd.enrollment)) * 100, 1)
                             ELSE NULL END AS rate_per_100
                     FROM restraint_data rd
                     WHERE rd.school_year = ?",
                    [$schoolYear]
                );
                $districtContext['state_avg'] = $stateAvg['rate_per_100'] ?? null;
            }
        }

        // ── Finding substantiation stats for chart ──
        $findingStats = [];
        if (!empty($findings)) {
            foreach ($findings as $f) {
                $cat = $f['allegation_category'] ?: 'Uncategorized';
                $key = $cat;
                if (!isset($findingStats[$key])) {
                    $findingStats[$key] = ['category' => $cat, 'total' => 0, 'substantiated' => 0, 'unsubstantiated' => 0, 'partially_substantiated' => 0];
                }
                $findingStats[$key]['total']++;
                $fnd = $f['finding'] ?? '';
                if ($fnd === 'substantiated') $findingStats[$key]['substantiated']++;
                elseif ($fnd === 'unsubstantiated') $findingStats[$key]['unsubstantiated']++;
                elseif ($fnd === 'partially_substantiated') $findingStats[$key]['partially_substantiated']++;
            }
            $findingStats = array_values($findingStats);
        }

        View::render('prs/detail', [
            'page_title'       => h($case['prs_number'] . ' — ' . ($case['case_title'] ?? 'PRS Case')),
            'page_description' => 'PRS case detail for ' . h($case['prs_number']),
            'page_stylesheet'  => 'prs',
            'case'             => $case,
            'events'           => $events,
            'findings'         => $findings,
            'documents'        => $documents,
            'documentsByType'  => $documentsByType,
            'allegations'      => $allegations,
            'relatedCases'     => $relatedCases,
            'deadline'         => $deadline,
            'deadlineTs'       => $deadlineTs,
            'daysUntilDeadline'=> $daysUntilDeadline,
            'isOverdue'        => $isOverdue,
            'districtContext'  => $districtContext,
            'findingStats'     => $findingStats,
        ]);
    }

    /** GET /prs/analytics — dashboard with charts */
    public function analytics(array $params = []): void
    {
        // ── Stat cards ──
        $totalCases = (int)Database::fetchColumn("SELECT COUNT(*) FROM prs_cases");
        $openCases  = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM prs_cases WHERE current_status IN ('filed','accepted','investigating','findings')"
        );
        $avgResolution = Database::fetchColumn(
            "SELECT ROUND(AVG(total_days_open)) FROM prs_cases WHERE closure_date IS NOT NULL AND total_days_open IS NOT NULL"
        );
        $deadlineCompliance = Database::fetchColumn(
            "SELECT ROUND(100 * SUM(CASE WHEN actual_resolution_date <= statutory_deadline THEN 1 ELSE 0 END) / NULLIF(COUNT(*),0), 1)
             FROM prs_cases WHERE statutory_deadline IS NOT NULL AND actual_resolution_date IS NOT NULL"
        );
        $substantiatedCount = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM prs_cases WHERE resolution_type = 'substantiated'"
        );
        $avgResolutionDays = $avgResolution ?? 0;

        // ── Category breakdown (existing) ──
        $categoryData = Database::fetchAll(
            "SELECT allegation_category, COUNT(*) as cnt
             FROM prs_findings
             WHERE allegation_category IS NOT NULL
             GROUP BY allegation_category
             ORDER BY cnt DESC LIMIT 10"
        );

        // ── Status distribution (existing) ──
        $statusData = Database::fetchAll(
            "SELECT current_status, COUNT(*) as cnt
             FROM prs_cases
             GROUP BY current_status
             ORDER BY FIELD(current_status, 'filed','accepted','investigating','findings','closed','appealed')"
        );

        // ── Filing trend (existing) ──
        $filingTrend = Database::fetchAll(
            "SELECT YEAR(filing_date) as yr, COUNT(*) as cnt
             FROM prs_cases
             WHERE filing_date IS NOT NULL
             GROUP BY yr ORDER BY yr"
        );

        // ── Resolution time histogram (existing) ──
        $resolutionTime = Database::fetchAll(
            "SELECT
                CASE
                    WHEN total_days_open <= 30 THEN '0-30d'
                    WHEN total_days_open <= 60 THEN '31-60d'
                    WHEN total_days_open <= 90 THEN '61-90d'
                    WHEN total_days_open <= 120 THEN '91-120d'
                    WHEN total_days_open <= 180 THEN '121-180d'
                    ELSE '181d+'
                END as bucket,
                COUNT(*) as cnt
             FROM prs_cases
             WHERE total_days_open IS NOT NULL
             GROUP BY bucket
             ORDER BY FIELD(bucket, '0-30d','31-60d','61-90d','91-120d','121-180d','181d+')"
        );

        // ── District volume (existing) ──
        $districtVolume = Database::fetchAll(
            "SELECT o.org_name, o.org_code, COUNT(*) as cnt
             FROM prs_cases pc
             JOIN organizations o ON pc.org_id = o.id
             GROUP BY o.id, o.org_name, o.org_code
             ORDER BY cnt DESC LIMIT 15"
        );

        // ── YoY change (existing) ──
        $yoyData = [];
        $prev = 0;
        foreach ($filingTrend as $row) {
            $yoyData[] = [
                'yr'  => $row['yr'],
                'cnt' => $row['cnt'],
                'change' => $prev > 0 ? round(100 * ($row['cnt'] - $prev) / $prev, 1) : 0,
            ];
            $prev = (int)$row['cnt'];
        }

        // ═══════════════════════════════════════
        //  WAVE 3: NEW ANALYTICS QUERIES
        // ═══════════════════════════════════════

        // ── 1. Multi-year filing trend with forecast ──
        $filingForecast = $this->computeForecast($filingTrend);

        // ── 2. Filing rate per 1000 students ──
        $filingRatePer1000 = Database::fetchAll(
            "SELECT o.org_name, o.org_code,
                    COUNT(pc.id) as case_count,
                    COALESCE(dd.students, 0) as enrollment
             FROM organizations o
             LEFT JOIN prs_cases pc ON pc.org_id = o.id
             LEFT JOIN discipline_data dd ON dd.org_id = o.id
                 AND dd.school_year = (SELECT MAX(school_year) FROM discipline_data WHERE org_id = o.id)
             WHERE o.org_type = 'Public School District' AND o.is_active = 1
             GROUP BY o.id, o.org_name, o.org_code, dd.students
             HAVING COALESCE(dd.students, 0) > 0
             ORDER BY (COUNT(pc.id) * 1000.0 / NULLIF(dd.students, 0)) DESC
             LIMIT 30"
        );

        // Compute state average rate
        $stateTotalCases = (int)Database::fetchColumn("SELECT COUNT(*) FROM prs_cases");
        $stateTotalEnrollment = (int)Database::fetchColumn(
            "SELECT SUM(dd.students) FROM discipline_data dd
             JOIN organizations o ON dd.org_id = o.id
             WHERE o.org_type = 'Public School District' AND o.is_active = 1
               AND dd.school_year = (SELECT MAX(school_year) FROM discipline_data d2 WHERE d2.org_id = dd.org_id)"
        );
        $stateAvgRate = $stateTotalEnrollment > 0 ? round($stateTotalCases * 1000.0 / $stateTotalEnrollment, 2) : 0;

        // ── 3. Month-over-month filing changes ──
        $monthlyFilings = Database::fetchAll(
            "SELECT DATE_FORMAT(filing_date, '%Y-%m') as month, COUNT(*) as cnt
             FROM prs_cases
             WHERE filing_date IS NOT NULL
             GROUP BY month
             ORDER BY month"
        );
        $momData = [];
        $prevMonth = 0;
        foreach ($monthlyFilings as $row) {
            $change = $prevMonth > 0 ? (int)$row['cnt'] - $prevMonth : 0;
            $color = $change >= 0 ? '#22c55e' : '#ef4444';
            $momData[] = [
                'month'  => $row['month'],
                'cnt'    => (int)$row['cnt'],
                'change' => $change,
                'color'  => $color,
            ];
            $prevMonth = (int)$row['cnt'];
        }
        // Trim to last 24 months for readability
        $momData = array_slice($momData, -24);

        // ── 4. Category trend divergence ──
        $categoryTrendRaw = Database::fetchAll(
            "SELECT YEAR(pc.filing_date) as yr, pf.allegation_category, COUNT(*) as cnt
             FROM prs_cases pc
             JOIN prs_findings pf ON pf.prs_case_id = pc.id
             WHERE pc.filing_date IS NOT NULL AND pf.allegation_category IS NOT NULL
             GROUP BY yr, pf.allegation_category
             ORDER BY yr, cnt DESC"
        );

        // Build: per year total, then per-category % of total
        $yearlyTotals = [];
        $categoryTrendFlat = [];
        foreach ($categoryTrendRaw as $row) {
            $yr = (int)$row['yr'];
            $yearlyTotals[$yr] = ($yearlyTotals[$yr] ?? 0) + (int)$row['cnt'];
            $categoryTrendFlat[$yr][$row['allegation_category']] = (int)$row['cnt'];
        }
        // Find top 8 categories by total over all years
        $catTotals = [];
        foreach ($categoryTrendRaw as $row) {
            $cat = $row['allegation_category'];
            $catTotals[$cat] = ($catTotals[$cat] ?? 0) + (int)$row['cnt'];
        }
        arsort($catTotals);
        $topCategories = array_slice(array_keys($catTotals), 0, 8);
        // Build final dataset: years => categories => %
        $categoryTrendYears = array_keys($yearlyTotals);
        sort($categoryTrendYears);
        $categoryTrendData = [];
        foreach ($categoryTrendYears as $yr) {
            $total = $yearlyTotals[$yr];
            $row = ['yr' => (string)$yr];
            foreach ($topCategories as $cat) {
                $row[$cat] = $total > 0 ? round(($categoryTrendFlat[$yr][$cat] ?? 0) * 100.0 / $total, 1) : 0;
            }
            $categoryTrendData[] = $row;
        }

        // ── 5. Resolution time by category (box plot approximation) ──
        $resByCatRaw = Database::fetchAll(
            "SELECT pf.allegation_category, pc.total_days_open
             FROM prs_cases pc
             JOIN prs_findings pf ON pf.prs_case_id = pc.id
             WHERE pc.total_days_open IS NOT NULL AND pf.allegation_category IS NOT NULL
             ORDER BY pf.allegation_category"
        );
        $resByCat = [];
        $catBuckets = [];
        foreach ($resByCatRaw as $row) {
            $catBuckets[$row['allegation_category']][] = (int)$row['total_days_open'];
        }
        foreach ($catBuckets as $cat => $days) {
            sort($days);
            $n = count($days);
            $avg = round(array_sum($days) / $n, 1);
            $q1 = $days[(int)floor($n * 0.25)] ?? $days[0];
            $q3 = $days[(int)floor($n * 0.75)] ?? $days[$n - 1];
            $min = $days[0];
            $max = $days[$n - 1];
            $resByCat[] = [
                'category' => $cat,
                'avg'      => $avg,
                'q1'       => $q1,
                'q3'       => $q3,
                'min'      => $min,
                'max'      => $max,
                'count'    => $n,
            ];
        }
        // Sort by avg descending, top 10
        usort($resByCat, fn($a, $b) => $b['avg'] <=> $a['avg']);
        $resByCat = array_slice($resByCat, 0, 10);

        // ── 6. Resolution time by district size (scatter) ──
        $scatterData = Database::fetchAll(
            "SELECT o.org_name, o.org_code,
                    COALESCE(dd.students, 0) as enrollment,
                    ROUND(AVG(pc.total_days_open), 1) as avg_days,
                    COUNT(pc.id) as case_count
             FROM organizations o
             JOIN prs_cases pc ON pc.org_id = o.id
             LEFT JOIN discipline_data dd ON dd.org_id = o.id
                 AND dd.school_year = (SELECT MAX(school_year) FROM discipline_data d2 WHERE d2.org_id = o.id)
             WHERE o.org_type = 'Public School District'
               AND o.is_active = 1
               AND pc.total_days_open IS NOT NULL
             GROUP BY o.id, o.org_name, o.org_code, dd.students
             HAVING COALESCE(dd.students, 0) > 0 AND COUNT(pc.id) >= 3
             ORDER BY dd.students"
        );

        // ── 7. Resolution rate funnel ──
        $funnelData = [
            ['stage' => 'Filed',              'cnt' => (int)Database::fetchColumn("SELECT COUNT(*) FROM prs_cases WHERE filing_date IS NOT NULL")],
            ['stage' => 'Accepted',           'cnt' => (int)Database::fetchColumn("SELECT COUNT(*) FROM prs_cases WHERE acceptance_date IS NOT NULL")],
            ['stage' => 'Investigating',      'cnt' => (int)Database::fetchColumn("SELECT COUNT(*) FROM prs_cases WHERE investigation_start IS NOT NULL")],
            ['stage' => 'Findings Issued',    'cnt' => (int)Database::fetchColumn("SELECT COUNT(*) FROM prs_cases WHERE findings_issued_date IS NOT NULL")],
            ['stage' => 'Closed',             'cnt' => (int)Database::fetchColumn("SELECT COUNT(*) FROM prs_cases WHERE closure_date IS NOT NULL")],
        ];

        // ── 8. Substantiation rate trend ──
        $subRateRaw = Database::fetchAll(
            "SELECT YEAR(pc.filing_date) as yr, pf.allegation_category,
                    SUM(CASE WHEN pf.finding = 'substantiated' THEN 1 ELSE 0 END) as sub_cnt,
                    COUNT(*) as total
             FROM prs_cases pc
             JOIN prs_findings pf ON pf.prs_case_id = pc.id
             WHERE pc.filing_date IS NOT NULL AND pf.allegation_category IS NOT NULL
             GROUP BY yr, pf.allegation_category
             ORDER BY yr"
        );
        // Find top 5 categories by total findings volume
        $catVolTotals = [];
        foreach ($subRateRaw as $row) {
            $catVolTotals[$row['allegation_category']] = ($catVolTotals[$row['allegation_category']] ?? 0) + (int)$row['total'];
        }
        arsort($catVolTotals);
        $top5SubCats = array_slice(array_keys($catVolTotals), 0, 5);
        // Build subst rate data: year => category => rate
        $subRateYears = [];
        $subRateByYr = [];
        foreach ($subRateRaw as $row) {
            $yr = (string)$row['yr'];
            $cat = $row['allegation_category'];
            if (!in_array($cat, $top5SubCats, true)) continue;
            $subRateByYr[$yr][$cat] = $row['total'] > 0 ? round((int)$row['sub_cnt'] * 100.0 / (int)$row['total'], 1) : 0;
            if (!in_array($yr, $subRateYears, true)) $subRateYears[] = $yr;
        }
        sort($subRateYears);
        $subRateData = [];
        foreach ($subRateYears as $yr) {
            $row = ['yr' => $yr];
            foreach ($top5SubCats as $cat) {
                $row[$cat] = $subRateByYr[$yr][$cat] ?? null;
            }
            $subRateData[] = $row;
        }

        // ── 9. Status distribution over time (stacked area) ──
        $statusOverTimeRaw = Database::fetchAll(
            "SELECT YEAR(pc.filing_date) as yr, pc.current_status, COUNT(*) as cnt
             FROM prs_cases pc
             WHERE pc.filing_date IS NOT NULL
             GROUP BY yr, pc.current_status
             ORDER BY yr, FIELD(pc.current_status, 'filed','accepted','investigating','findings','closed','appealed')"
        );
        $statusYears = [];
        $statusOverTime = [];
        foreach ($statusOverTimeRaw as $row) {
            $yr = (string)$row['yr'];
            if (!in_array($yr, $statusYears, true)) $statusYears[] = $yr;
            $statusOverTime[$yr][$row['current_status']] = (int)$row['cnt'];
        }
        sort($statusYears);
        $statusStyles = [
            'filed'          => '#60a5fa',
            'accepted'       => '#22c55e',
            'investigating'  => '#f59e0b',
            'findings'       => '#ff5a1f',
            'closed'         => '#a0a0a0',
            'appealed'       => '#a78bfa',
        ];

        // ── 10. District PRS volume ranking ──
        $districtRanking = Database::fetchAll(
            "SELECT o.org_name, o.org_code,
                    COUNT(pc.id) as total_cases,
                    SUM(CASE WHEN pc.current_status IN ('filed','accepted','investigating','findings') THEN 1 ELSE 0 END) as open_cases,
                    ROUND(100 * SUM(CASE WHEN pc.resolution_type = 'substantiated' THEN 1 ELSE 0 END) / NULLIF(COUNT(pc.id), 0), 1) as subst_rate,
                    ROUND(AVG(CASE WHEN pc.closure_date IS NOT NULL THEN pc.total_days_open END), 1) as avg_res_days,
                    COALESCE(MAX(dd.students), 0) as enrollment
             FROM organizations o
             JOIN prs_cases pc ON pc.org_id = o.id
             LEFT JOIN discipline_data dd ON dd.org_id = o.id
                 AND dd.school_year = (SELECT MAX(school_year) FROM discipline_data d2 WHERE d2.org_id = o.id)
             WHERE o.org_type = 'Public School District' AND o.is_active = 1
             GROUP BY o.id, o.org_name, o.org_code, dd.students
             ORDER BY total_cases DESC
             LIMIT 25"
        );

        // ── 11. District PRS trend comparison (multi-line) ──
        // Top 5 districts by volume for default comparison
        $topDistricts = Database::fetchAll(
            "SELECT o.org_code, o.org_name, COUNT(pc.id) as cnt
             FROM organizations o
             JOIN prs_cases pc ON pc.org_id = o.id
             WHERE o.org_type = 'Public School District' AND o.is_active = 1
             GROUP BY o.id, o.org_code, o.org_name
             ORDER BY cnt DESC LIMIT 5"
        );
        $districtCompareData = [];
        foreach ($topDistricts as $d) {
            $trend = Database::fetchAll(
                "SELECT YEAR(filing_date) as yr, COUNT(*) as cnt
                 FROM prs_cases
                 WHERE org_id = (SELECT id FROM organizations WHERE org_code = ?)
                   AND filing_date IS NOT NULL
                 GROUP BY yr ORDER BY yr",
                [$d['org_code']]
            );
            $districtCompareData[$d['org_name']] = $trend;
        }
        // Merge into year-indexed map for multi-line
        $compareYearsSet = [];
        foreach ($districtCompareData as $rows) {
            foreach ($rows as $r) $compareYearsSet[(int)$r['yr']] = true;
        }
        $compareYears = array_keys($compareYearsSet);
        sort($compareYears);

        // ── 12. County-level PRS aggregation ──
        $countyMap = self::maTownCountyMap();
        $countyPrsRaw = Database::fetchAll(
            "SELECT o.town, COUNT(pc.id) as case_count,
                    SUM(CASE WHEN pc.resolution_type = 'substantiated' THEN 1 ELSE 0 END) as subst_count,
                    ROUND(AVG(CASE WHEN pc.closure_date IS NOT NULL THEN pc.total_days_open END), 1) as avg_res_days
             FROM organizations o
             JOIN prs_cases pc ON pc.org_id = o.id
             WHERE o.org_type = 'Public School District' AND o.is_active = 1
             GROUP BY o.town"
        );
        $countyData = [];
        foreach ($countyPrsRaw as $row) {
            $county = $countyMap[$row['town']] ?? 'Out of State / Unknown';
            if (!isset($countyData[$county])) {
                $countyData[$county] = ['county' => $county, 'cases' => 0, 'subst' => 0, 'avg_days_sum' => 0, 'avg_days_n' => 0];
            }
            $countyData[$county]['cases'] += (int)$row['case_count'];
            $countyData[$county]['subst'] += (int)$row['subst_count'];
            if ($row['avg_res_days'] !== null) {
                $countyData[$county]['avg_days_sum'] += (float)$row['avg_res_days'];
                $countyData[$county]['avg_days_n']++;
            }
        }
        $countyList = array_values($countyData);
        foreach ($countyList as &$c) {
            $c['avg_res_days'] = $c['avg_days_n'] > 0 ? round($c['avg_days_sum'] / $c['avg_days_n'], 1) : 0;
            $c['subst_rate'] = $c['cases'] > 0 ? round($c['subst'] * 100.0 / $c['cases'], 1) : 0;
        }
        usort($countyList, fn($a, $b) => $b['cases'] <=> $a['cases']);

        // ── 13. Urban vs suburban vs rural ──
        $geoClassData = Database::fetchAll(
            "SELECT
                CASE
                    WHEN COALESCE(dd.students, 0) >= 10000 THEN 'Urban (10K+)'
                    WHEN COALESCE(dd.students, 0) >= 3000 THEN 'Suburban (3K-10K)'
                    WHEN COALESCE(dd.students, 0) > 0 THEN 'Rural (<3K)'
                    ELSE 'Unknown'
                END as geo_class,
                COUNT(DISTINCT o.id) as district_count,
                COUNT(pc.id) as total_cases,
                ROUND(COUNT(pc.id) * 1.0 / NULLIF(COUNT(DISTINCT o.id), 0), 1) as cases_per_district,
                ROUND(100 * SUM(CASE WHEN pc.resolution_type = 'substantiated' THEN 1 ELSE 0 END) / NULLIF(COUNT(pc.id), 0), 1) as subst_rate,
                ROUND(AVG(CASE WHEN pc.closure_date IS NOT NULL THEN pc.total_days_open END), 1) as avg_res_days
             FROM organizations o
             LEFT JOIN prs_cases pc ON pc.org_id = o.id
             LEFT JOIN discipline_data dd ON dd.org_id = o.id
                 AND dd.school_year = (SELECT MAX(school_year) FROM discipline_data d2 WHERE d2.org_id = o.id)
             WHERE o.org_type = 'Public School District' AND o.is_active = 1
             GROUP BY geo_class
             ORDER BY FIELD(geo_class, 'Urban (10K+)', 'Suburban (3K-10K)', 'Rural (<3K)', 'Unknown')"
        );

        // ── 14. District PRS risk score ──
        $riskRaw = Database::fetchAll(
            "SELECT o.org_code, o.org_name,
                    COUNT(pc.id) as case_count,
                    COALESCE(dd.students, 0) as enrollment,
                    ROUND(100 * SUM(CASE WHEN pc.resolution_type = 'substantiated' THEN 1 ELSE 0 END) / NULLIF(COUNT(pc.id), 0), 1) as subst_rate,
                    ROUND(AVG(CASE WHEN pc.closure_date IS NOT NULL THEN pc.total_days_open END), 1) as avg_res_days,
                    COALESCE(rd.total_restraints, 0) as restraints
             FROM organizations o
             JOIN prs_cases pc ON pc.org_id = o.id
             LEFT JOIN discipline_data dd ON dd.org_id = o.id
                 AND dd.school_year = (SELECT MAX(school_year) FROM discipline_data d2 WHERE d2.org_id = o.id)
             LEFT JOIN (
                 SELECT o2.id, SUM(r.total_restraints) as total_restraints
                 FROM organizations o2
                 JOIN restraint_data r ON r.org_id = o2.id
                 JOIN organizations s ON s.parent_org_id = o2.id AND s.org_type IN ('Public School','Charter School','Vocational School')
                 WHERE o2.org_type = 'Public School District'
                 GROUP BY o2.id
             ) rd ON rd.id = o.id
             WHERE o.org_type = 'Public School District' AND o.is_active = 1
             GROUP BY o.id, o.org_code, o.org_name, dd.students, rd.total_restraints
             HAVING COUNT(pc.id) >= 3"
        );
        // Compute risk scores
        $maxRate = 0; $maxRestr = 0; $maxRes = 0; $maxSubst = 0;
        foreach ($riskRaw as $r) {
            $rate = $r['enrollment'] > 0 ? ($r['case_count'] * 1000.0 / $r['enrollment']) : 0;
            if ($rate > $maxRate) $maxRate = $rate;
            if ((int)$r['restraints'] > $maxRestr) $maxRestr = (int)$r['restraints'];
            if ((float)$r['avg_res_days'] > $maxRes) $maxRes = (float)$r['avg_res_days'];
            if ((float)$r['subst_rate'] > $maxSubst) $maxSubst = (float)$r['subst_rate'];
        }
        $riskData = [];
        foreach ($riskRaw as $r) {
            $rate = $r['enrollment'] > 0 ? ($r['case_count'] * 1000.0 / $r['enrollment']) : 0;
            $score = 0;
            $score += ($maxRate > 0 ? ($rate / $maxRate) : 0) * 35;
            $score += ($maxSubst > 0 ? ((float)$r['subst_rate'] / $maxSubst) : 0) * 30;
            $score += ($maxRes > 0 ? ((float)$r['avg_res_days'] / $maxRes) : 0) * 25;
            $score += ($maxRestr > 0 ? ((int)$r['restraints'] / $maxRestr) : 0) * 10;
            $riskData[] = [
                'org_code'    => $r['org_code'],
                'org_name'    => $r['org_name'],
                'case_count'  => (int)$r['case_count'],
                'filing_rate' => round($rate, 2),
                'subst_rate'  => (float)$r['subst_rate'],
                'avg_res_days' => (float)$r['avg_res_days'],
                'risk_score'  => round($score, 1),
            ];
        }
        usort($riskData, fn($a, $b) => $b['risk_score'] <=> $a['risk_score']);
        $riskData = array_slice($riskData, 0, 20);

        // ── 15. YoY change heatmap ──
        $heatmapRaw = Database::fetchAll(
            "SELECT o.org_code, o.org_name, YEAR(pc.filing_date) as yr, COUNT(*) as cnt
             FROM prs_cases pc
             JOIN organizations o ON pc.org_id = o.id
             WHERE pc.filing_date IS NOT NULL
               AND o.org_type = 'Public School District'
               AND o.is_active = 1
             GROUP BY o.org_code, o.org_name, yr
             HAVING COUNT(*) >= 5
             ORDER BY o.org_name, yr"
        );
        $heatYears = [];
        $heatDistricts = [];
        $heatGrid = [];
        foreach ($heatmapRaw as $row) {
            $yr = (int)$row['yr'];
            $code = $row['org_code'];
            if (!in_array($yr, $heatYears, true)) $heatYears[] = $yr;
            if (!isset($heatDistricts[$code])) $heatDistricts[$code] = $row['org_name'];
            $heatGrid[$code][$yr] = (int)$row['cnt'];
        }
        sort($heatYears);
        // Only keep last 5 years
        $heatYears = array_slice($heatYears, -5);
        // Top 15 by total
        $heatDistrictTotals = [];
        foreach ($heatGrid as $code => $yrs) {
            $heatDistrictTotals[$code] = array_sum(array_intersect_key($yrs, array_flip($heatYears)));
        }
        arsort($heatDistrictTotals);
        $heatTopCodes = array_slice(array_keys($heatDistrictTotals), 0, 15);

        // ── 16. COVID impact analysis ──
        $covidData = Database::fetchAll(
            "SELECT
                CASE
                    WHEN YEAR(filing_date) < 2020 THEN 'Pre-COVID (2016-2019)'
                    WHEN YEAR(filing_date) <= 2021 THEN 'COVID Era (2020-2021)'
                    ELSE 'Post-COVID (2022+)'
                END as period,
                COUNT(*) as case_count,
                ROUND(AVG(total_days_open), 1) as avg_days,
                ROUND(100 * SUM(CASE WHEN resolution_type = 'substantiated' THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0), 1) as subst_rate
             FROM prs_cases
             WHERE filing_date IS NOT NULL AND YEAR(filing_date) >= 2016
             GROUP BY period
             ORDER BY FIELD(period, 'Pre-COVID (2016-2019)', 'COVID Era (2020-2021)', 'Post-COVID (2022+)')"
        );


        // ── Wave 3b: Filing trends & resolution analytics ──
        $filing_trend = Database::fetchAll(
            "SELECT YEAR(filing_date) as yr, COUNT(*) as cnt
             FROM prs_cases
             WHERE filing_date IS NOT NULL
             GROUP BY yr ORDER BY yr"
        );

        $category_trend = Database::fetchAll(
            "SELECT YEAR(c.filing_date) as yr,
                    COALESCE(JSON_UNQUOTE(JSON_EXTRACT(c.allegations, '$.category')), 'Other') as cat,
                    COUNT(*) as cnt
             FROM prs_cases c
             WHERE filing_date IS NOT NULL
             GROUP BY yr, cat
             ORDER BY yr, cnt DESC"
        );

        $resolution_time = Database::fetchAll(
            "SELECT
                CASE
                    WHEN DATEDIFF(COALESCE(closure_date, NOW()), filing_date) <= 60 THEN '0-60'
                    WHEN DATEDIFF(COALESCE(closure_date, NOW()), filing_date) <= 120 THEN '61-120'
                    ELSE '120+'
                END as bucket,
                COUNT(*) as cnt
             FROM prs_cases
             WHERE filing_date IS NOT NULL
             GROUP BY bucket
             ORDER BY FIELD(bucket, '0-60', '61-120', '120+')"
        );

        $substantiation_rate = Database::fetchAll(
            "SELECT YEAR(filing_date) as yr,
                    ROUND(SUM(CASE WHEN resolution_type = 'substantiated' THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as rate
             FROM prs_cases
             WHERE filing_date IS NOT NULL AND resolution_type IS NOT NULL
             GROUP BY yr ORDER BY yr"
        );

        $district_volume = Database::fetchAll(
            "SELECT o.org_name, COUNT(*) as cnt
             FROM prs_cases c
             JOIN organizations o ON c.org_id = o.id
             GROUP BY o.id, o.org_name
             ORDER BY cnt DESC LIMIT 15"
        );
        View::render('prs/analytics', [
            'page_title'          => 'PRS Analytics',
            'page_description'    => 'Analytics dashboard for Problem Resolution System complaints.',
            'page_stylesheet'     => 'data',
            'totalCases'          => $totalCases,
            'openCases'           => $openCases,
            'avgResolution'       => $avgResolution,
            'deadlineCompliance'  => $deadlineCompliance,
            'substantiatedCount'  => $substantiatedCount,
            'categoryData'        => $categoryData,
            'statusData'          => $statusData,
            'filingTrend'         => $filingTrend,
            'resolutionTime'      => $resolutionTime,
            'districtVolume'      => $districtVolume,
            'yoyData'             => $yoyData,
            // New
            'filingForecast'      => $filingForecast,
            'filingRatePer1000'   => $filingRatePer1000,
            'stateAvgRate'        => $stateAvgRate,
            'momData'             => $momData,
            'categoryTrendData'   => $categoryTrendData,
            'topCategories'       => $topCategories,
            'categoryTrendYears'  => $categoryTrendYears,
            'resByCat'            => $resByCat,
            'scatterData'         => $scatterData,
            'funnelData'          => $funnelData,
            'subRateData'         => $subRateData,
            'top5SubCats'         => $top5SubCats,
            'subRateYears'        => $subRateYears,
            'statusOverTime'      => $statusOverTime,
            'statusYears'         => $statusYears,
            'statusStyles'        => $statusStyles,
            'districtRanking'     => $districtRanking,
            'districtCompareData' => $districtCompareData,
            'compareYears'        => $compareYears,
            'countyList'          => $countyList,
            'geoClassData'        => $geoClassData,
            'riskData'            => $riskData,
            'heatYears'           => $heatYears,
            'heatGrid'            => $heatGrid,
            'heatTopCodes'        => $heatTopCodes,
            'heatDistricts'       => $heatDistricts,
            'covidData'           => $covidData,
            'filing_trend'        => $filing_trend,
            'category_trend'      => $category_trend,
            'resolution_time'     => $resolution_time,
            'substantiation_rate' => $substantiation_rate,
            'district_volume'     => $district_volume,
        ]);
    }

    /**
     * Compute linear regression forecast for filing trend.
     * Returns array with historical data + forecast points.
     */
    private function computeForecast(array $filingTrend): array
    {
        if (count($filingTrend) < 3) {
            return ['historical' => $filingTrend, 'forecast' => []];
        }

        $years = array_map('intval', array_column($filingTrend, 'yr'));
        $counts = array_map('intval', array_column($filingTrend, 'cnt'));
        $n = count($years);
        $meanX = array_sum($years) / $n;
        $meanY = array_sum($counts) / $n;

        $num = 0; $den = 0;
        for ($i = 0; $i < $n; $i++) {
            $dx = $years[$i] - $meanX;
            $num += $dx * ($counts[$i] - $meanY);
            $den += $dx * $dx;
        }
        $slope = $den != 0 ? $num / $den : 0;
        $intercept = $meanY - $slope * $meanX;

        $lastYear = max($years);
        $forecast = [];
        for ($y = $lastYear + 1; $y <= $lastYear + 2; $y++) {
            $forecast[] = [
                'yr'  => (string)$y,
                'cnt' => max(0, round($slope * $y + $intercept)),
            ];
        }

        return ['historical' => $filingTrend, 'forecast' => $forecast];
    }

    /** MA town → county mapping for county-level aggregation. */
    private static function maTownCountyMap(): array
    {
        return [
            // Barnstable County
            'Barnstable' => 'Barnstable', 'Bourne' => 'Barnstable', 'Brewster' => 'Barnstable',
            'Chatham' => 'Barnstable', 'Dennis' => 'Barnstable', 'Eastham' => 'Barnstable',
            'Falmouth' => 'Barnstable', 'Harwich' => 'Barnstable', 'Mashpee' => 'Barnstable',
            'Orleans' => 'Barnstable', 'Provincetown' => 'Barnstable', 'Sandwich' => 'Barnstable',
            'Truro' => 'Barnstable', 'Wellfleet' => 'Barnstable', 'Yarmouth' => 'Barnstable',
            // Berkshire County
            'Adams' => 'Berkshire', 'Alford' => 'Berkshire', 'Becket' => 'Berkshire',
            'Cheshire' => 'Berkshire', 'Clarksburg' => 'Berkshire', 'Dalton' => 'Berkshire',
            'Egremont' => 'Berkshire', 'Florida' => 'Berkshire', 'Great Barrington' => 'Berkshire',
            'Hancock' => 'Berkshire', 'Hinsdale' => 'Berkshire', 'Lanesborough' => 'Berkshire',
            'Lee' => 'Berkshire', 'Lenox' => 'Berkshire', 'Monterey' => 'Berkshire',
            'Mount Washington' => 'Berkshire', 'New Ashford' => 'Berkshire', 'New Marlborough' => 'Berkshire',
            'North Adams' => 'Berkshire', 'Otis' => 'Berkshire', 'Peru' => 'Berkshire',
            'Pittsfield' => 'Berkshire', 'Richmond' => 'Berkshire', 'Sandisfield' => 'Berkshire',
            'Savoy' => 'Berkshire', 'Sheffield' => 'Berkshire', 'Stockbridge' => 'Berkshire',
            'Tyringham' => 'Berkshire', 'Washington' => 'Berkshire', 'West Stockbridge' => 'Berkshire',
            'Williamstown' => 'Berkshire', 'Windsor' => 'Berkshire',
            // Bristol County
            'Acushnet' => 'Bristol', 'Attleboro' => 'Bristol', 'Berkley' => 'Bristol',
            'Dartmouth' => 'Bristol', 'Dighton' => 'Bristol', 'Easton' => 'Bristol',
            'Fairhaven' => 'Bristol', 'Fall River' => 'Bristol', 'Freetown' => 'Bristol',
            'Mansfield' => 'Bristol', 'New Bedford' => 'Bristol', 'North Attleborough' => 'Bristol',
            'Norton' => 'Bristol', 'Raynham' => 'Bristol', 'Rehoboth' => 'Bristol',
            'Seekonk' => 'Bristol', 'Somerset' => 'Bristol', 'Swansea' => 'Bristol',
            'Taunton' => 'Bristol', 'Westport' => 'Bristol',
            // Dukes County
            'Aquinnah' => 'Dukes', 'Chilmark' => 'Dukes', 'Edgartown' => 'Dukes',
            'Gosnold' => 'Dukes', 'Oak Bluffs' => 'Dukes', 'Tisbury' => 'Dukes',
            'West Tisbury' => 'Dukes',
            // Essex County
            'Amesbury' => 'Essex', 'Andover' => 'Essex', 'Beverly' => 'Essex',
            'Boxford' => 'Essex', 'Danvers' => 'Essex', 'Essex' => 'Essex',
            'Georgetown' => 'Essex', 'Gloucester' => 'Essex', 'Groveland' => 'Essex',
            'Hamilton' => 'Essex', 'Haverhill' => 'Essex', 'Ipswich' => 'Essex',
            'Lawrence' => 'Essex', 'Lynn' => 'Essex', 'Lynnfield' => 'Essex',
            'Manchester-by-the-Sea' => 'Essex', 'Marblehead' => 'Essex', 'Merrimac' => 'Essex',
            'Methuen' => 'Essex', 'Middleton' => 'Essex', 'Nahant' => 'Essex',
            'Newbury' => 'Essex', 'Newburyport' => 'Essex', 'North Andover' => 'Essex',
            'Peabody' => 'Essex', 'Rockport' => 'Essex', 'Rowley' => 'Essex',
            'Salem' => 'Essex', 'Salisbury' => 'Essex', 'Saugus' => 'Essex',
            'Swampscott' => 'Essex', 'Topsfield' => 'Essex', 'Wenham' => 'Essex',
            'West Newbury' => 'Essex',
            // Franklin County
            'Ashfield' => 'Franklin', 'Bernardston' => 'Franklin', 'Buckland' => 'Franklin',
            'Charlemont' => 'Franklin', 'Colrain' => 'Franklin', 'Conway' => 'Franklin',
            'Deerfield' => 'Franklin', 'Erving' => 'Franklin', 'Gill' => 'Franklin',
            'Greenfield' => 'Franklin', 'Hawley' => 'Franklin', 'Heath' => 'Franklin',
            'Leverett' => 'Franklin', 'Leyden' => 'Franklin', 'Monroe' => 'Franklin',
            'Montague' => 'Franklin', 'New Salem' => 'Franklin', 'Northfield' => 'Franklin',
            'Orange' => 'Franklin', 'Rowe' => 'Franklin', 'Shelburne' => 'Franklin',
            'Shutesbury' => 'Franklin', 'Sunderland' => 'Franklin', 'Warwick' => 'Franklin',
            'Wendell' => 'Franklin', 'Whately' => 'Franklin',
            // Hampden County
            'Agawam' => 'Hampden', 'Blandford' => 'Hampden', 'Brimfield' => 'Hampden',
            'Chester' => 'Hampden', 'Chicopee' => 'Hampden', 'East Longmeadow' => 'Hampden',
            'Granville' => 'Hampden', 'Hampden' => 'Hampden', 'Holland' => 'Hampden',
            'Holyoke' => 'Hampden', 'Longmeadow' => 'Hampden', 'Ludlow' => 'Hampden',
            'Monson' => 'Hampden', 'Montgomery' => 'Hampden', 'Palmer' => 'Hampden',
            'Russell' => 'Hampden', 'Southwick' => 'Hampden', 'Springfield' => 'Hampden',
            'Tolland' => 'Hampden', 'Wales' => 'Hampden', 'West Springfield' => 'Hampden',
            'Westfield' => 'Hampden', 'Wilbraham' => 'Hampden',
            // Hampshire County
            'Amherst' => 'Hampshire', 'Belchertown' => 'Hampshire', 'Chesterfield' => 'Hampshire',
            'Cummington' => 'Hampshire', 'Easthampton' => 'Hampshire', 'Goshen' => 'Hampshire',
            'Granby' => 'Hampshire', 'Hadley' => 'Hampshire', 'Hatfield' => 'Hampshire',
            'Huntington' => 'Hampshire', 'Middlefield' => 'Hampshire', 'Northampton' => 'Hampshire',
            'Pelham' => 'Hampshire', 'Plainfield' => 'Hampshire', 'South Hadley' => 'Hampshire',
            'Southampton' => 'Hampshire', 'Ware' => 'Hampshire', 'Westhampton' => 'Hampshire',
            'Williamsburg' => 'Hampshire', 'Worthington' => 'Hampshire',
            // Middlesex County
            'Acton' => 'Middlesex', 'Arlington' => 'Middlesex', 'Ashby' => 'Middlesex',
            'Ashland' => 'Middlesex', 'Ayer' => 'Middlesex', 'Bedford' => 'Middlesex',
            'Belmont' => 'Middlesex', 'Billerica' => 'Middlesex', 'Boxborough' => 'Middlesex',
            'Burlington' => 'Middlesex', 'Cambridge' => 'Middlesex', 'Carlisle' => 'Middlesex',
            'Chelmsford' => 'Middlesex', 'Concord' => 'Middlesex', 'Dracut' => 'Middlesex',
            'Dunstable' => 'Middlesex', 'Everett' => 'Middlesex', 'Framingham' => 'Middlesex',
            'Groton' => 'Middlesex', 'Holliston' => 'Middlesex', 'Hopkinton' => 'Middlesex',
            'Hudson' => 'Middlesex', 'Lexington' => 'Middlesex', 'Lincoln' => 'Middlesex',
            'Littleton' => 'Middlesex', 'Lowell' => 'Middlesex', 'Malden' => 'Middlesex',
            'Marlborough' => 'Middlesex', 'Maynard' => 'Middlesex', 'Medford' => 'Middlesex',
            'Melrose' => 'Middlesex', 'Natick' => 'Middlesex', 'Newton' => 'Middlesex',
            'North Reading' => 'Middlesex', 'Pepperell' => 'Middlesex', 'Reading' => 'Middlesex',
            'Sherborn' => 'Middlesex', 'Shirley' => 'Middlesex', 'Somerville' => 'Middlesex',
            'Stoneham' => 'Middlesex', 'Stow' => 'Middlesex', 'Sudbury' => 'Middlesex',
            'Tewksbury' => 'Middlesex', 'Townsend' => 'Middlesex', 'Tyngsborough' => 'Middlesex',
            'Wakefield' => 'Middlesex', 'Waltham' => 'Middlesex', 'Watertown' => 'Middlesex',
            'Wayland' => 'Middlesex', 'Westford' => 'Middlesex', 'Weston' => 'Middlesex',
            'Wilmington' => 'Middlesex', 'Winchester' => 'Middlesex', 'Woburn' => 'Middlesex',
            // Nantucket County
            'Nantucket' => 'Nantucket',
            // Norfolk County
            'Avon' => 'Norfolk', 'Bellingham' => 'Norfolk', 'Braintree' => 'Norfolk',
            'Brookline' => 'Norfolk', 'Canton' => 'Norfolk', 'Cohasset' => 'Norfolk',
            'Dedham' => 'Norfolk', 'Dover' => 'Norfolk', 'Foxborough' => 'Norfolk',
            'Franklin' => 'Norfolk', 'Holbrook' => 'Norfolk', 'Medfield' => 'Norfolk',
            'Medway' => 'Norfolk', 'Millis' => 'Norfolk', 'Milton' => 'Norfolk',
            'Needham' => 'Norfolk', 'Norfolk' => 'Norfolk', 'Norwood' => 'Norfolk',
            'Plainville' => 'Norfolk', 'Quincy' => 'Norfolk', 'Randolph' => 'Norfolk',
            'Sharon' => 'Norfolk', 'Stoughton' => 'Norfolk', 'Walpole' => 'Norfolk',
            'Wellesley' => 'Norfolk', 'Westwood' => 'Norfolk', 'Weymouth' => 'Norfolk',
            'Wrentham' => 'Norfolk',
            // Plymouth County
            'Abington' => 'Plymouth', 'Bridgewater' => 'Plymouth', 'Brockton' => 'Plymouth',
            'Carver' => 'Plymouth', 'Duxbury' => 'Plymouth', 'East Bridgewater' => 'Plymouth',
            'Halifax' => 'Plymouth', 'Hanover' => 'Plymouth', 'Hanson' => 'Plymouth',
            'Hingham' => 'Plymouth', 'Hull' => 'Plymouth', 'Kingston' => 'Plymouth',
            'Lakeville' => 'Plymouth', 'Marion' => 'Plymouth', 'Marshfield' => 'Plymouth',
            'Mattapoisett' => 'Plymouth', 'Middleborough' => 'Plymouth', 'Norwell' => 'Plymouth',
            'Pembroke' => 'Plymouth', 'Plymouth' => 'Plymouth', 'Plympton' => 'Plymouth',
            'Rochester' => 'Plymouth', 'Rockland' => 'Plymouth', 'Scituate' => 'Plymouth',
            'Wareham' => 'Plymouth', 'West Bridgewater' => 'Plymouth', 'Whitman' => 'Plymouth',
            // Suffolk County
            'Boston' => 'Suffolk', 'Chelsea' => 'Suffolk', 'Revere' => 'Suffolk',
            'Winthrop' => 'Suffolk',
            // Worcester County
            'Ashburnham' => 'Worcester', 'Athol' => 'Worcester', 'Auburn' => 'Worcester',
            'Barre' => 'Worcester', 'Berlin' => 'Worcester', 'Blackstone' => 'Worcester',
            'Bolton' => 'Worcester', 'Boylston' => 'Worcester', 'Brookfield' => 'Worcester',
            'Charlton' => 'Worcester', 'Clinton' => 'Worcester', 'Douglas' => 'Worcester',
            'Dudley' => 'Worcester', 'East Brookfield' => 'Worcester', 'Fitchburg' => 'Worcester',
            'Gardner' => 'Worcester', 'Grafton' => 'Worcester', 'Hardwick' => 'Worcester',
            'Harvard' => 'Worcester', 'Holden' => 'Worcester', 'Hopedale' => 'Worcester',
            'Hubbardston' => 'Worcester', 'Lancaster' => 'Worcester', 'Leicester' => 'Worcester',
            'Leominster' => 'Worcester', 'Lunenburg' => 'Worcester', 'Mendon' => 'Worcester',
            'Milford' => 'Worcester', 'Millbury' => 'Worcester', 'Millville' => 'Worcester',
            'New Braintree' => 'Worcester', 'North Brookfield' => 'Worcester', 'Northborough' => 'Worcester',
            'Northbridge' => 'Worcester', 'Oakham' => 'Worcester', 'Oxford' => 'Worcester',
            'Paxton' => 'Worcester', 'Petersham' => 'Worcester', 'Phillipston' => 'Worcester',
            'Princeton' => 'Worcester', 'Royalston' => 'Worcester', 'Rutland' => 'Worcester',
            'Shrewsbury' => 'Worcester', 'Southborough' => 'Worcester', 'Southbridge' => 'Worcester',
            'Spencer' => 'Worcester', 'Sterling' => 'Worcester', 'Sturbridge' => 'Worcester',
            'Sutton' => 'Worcester', 'Templeton' => 'Worcester', 'Upton' => 'Worcester',
            'Uxbridge' => 'Worcester', 'Warren' => 'Worcester', 'Webster' => 'Worcester',
            'West Boylston' => 'Worcester', 'West Brookfield' => 'Worcester', 'Westborough' => 'Worcester',
            'Westminster' => 'Worcester', 'Winchendon' => 'Worcester', 'Worcester' => 'Worcester',
        ];
    }

    /** GET /prs/district/{code} — all PRS cases for a district */
    public function districtView(array $params = []): void
    {
        $code = strtoupper($params['code'] ?? '');

        $district = Database::fetch(
            "SELECT * FROM organizations WHERE org_code = ? AND is_active = 1", [$code]
        );

        if (!$district) {
            http_response_code(404);
            View::render('errors/404', ['page_title' => 'District Not Found']);
            return;
        }

        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 25;
        $offset  = ($page - 1) * $perPage;

        $total = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM prs_cases WHERE org_id = ?",
            [(int)$district['id']]
        );

        $cases = Database::fetchAll(
            "SELECT prs_number, case_title, current_status, filing_date, closure_date, total_days_open
             FROM prs_cases
             WHERE org_id = ?
             ORDER BY filing_date DESC
             LIMIT ? OFFSET ?",
            [(int)$district['id'], $perPage, $offset]
        );

        // Summary stats
        $openCases = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM prs_cases WHERE org_id = ? AND current_status IN ('filed','accepted','investigating','findings')",
            [(int)$district['id']]
        );

        $substantiated = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM prs_cases WHERE org_id = ? AND resolution_type = 'substantiated'",
            [(int)$district['id']]
        );

        // Filing trend for charts
        $filingTrend = Database::fetchAll(
            "SELECT YEAR(filing_date) as yr, COUNT(*) as cnt
             FROM prs_cases
             WHERE org_id = ? AND filing_date IS NOT NULL
             GROUP BY yr ORDER BY yr",
            [(int)$district['id']]
        );

        // Category breakdown for this district
        $categoryBreakdown = Database::fetchAll(
            "SELECT pf.allegation_category, COUNT(*) as cnt
             FROM prs_findings pf
             JOIN prs_cases pc ON pf.prs_case_id = pc.id
             WHERE pc.org_id = ? AND pf.allegation_category IS NOT NULL
             GROUP BY pf.allegation_category
             ORDER BY cnt DESC LIMIT 10",
            [(int)$district['id']]
        );

        // Status distribution for this district
        $statusDist = Database::fetchAll(
            "SELECT current_status, COUNT(*) as cnt
             FROM prs_cases
             WHERE org_id = ?
             GROUP BY current_status
             ORDER BY FIELD(current_status, 'filed','accepted','investigating','findings','closed','appealed')",
            [(int)$district['id']]
        );

        View::render('prs/district', [
            'page_title'       => 'PRS Cases — ' . h($district['org_name']),
            'page_description' => 'PRS complaint cases involving ' . h($district['org_name']),
            'page_stylesheet'  => 'data',
            'district'         => $district,
            'cases'            => $cases,
            'pagination'       => paginate($total, $perPage),
            'total'            => $total,
            'openCases'        => $openCases,
            'substantiated'    => $substantiated,
            'filingTrend'      => $filingTrend,
            'categoryBreakdown' => $categoryBreakdown,
            'statusDist'       => $statusDist,
        ]);
    }

    // ──────────────────────────────────────────────
    //  CROSS-REFERENCE: PRS + DESE
    // ──────────────────────────────────────────────

    /** GET /prs/cross-ref — PRS-to-DESE correlation dashboard */
    public function crossRef(array $params = []): void
    {
        $districtCode = $_GET['district'] ?? '';

        // ── District selector dropdown ──
        $districts = Database::fetchAllCached(
            "SELECT o.org_code, o.org_name
             FROM organizations o
             WHERE o.org_type = 'Public School District' AND o.is_active = 1
               AND EXISTS (SELECT 1 FROM prs_cases WHERE org_id = o.id)
             ORDER BY o.org_name",
            [], 300
        );

        $chartHtml = '';
        $correlationTable = [];
        $mergedData = [];
        $selectedDistrict = null;

        // ── Pearson correlation helper ──
        $pearsonR = function(array $x, array $y): float {
            $n = count($x);
            if ($n < 3) return 0;
            $mx = array_sum($x) / $n;
            $my = array_sum($y) / $n;
            $num = 0; $dx2 = 0; $dy2 = 0;
            for ($i = 0; $i < $n; $i++) {
                $dx = $x[$i] - $mx;
                $dy = $y[$i] - $my;
                $num += $dx * $dy;
                $dx2 += $dx * $dx;
                $dy2 += $dy * $dy;
            }
            $den = sqrt($dx2 * $dy2);
            return $den > 0 ? round($num / $den, 4) : 0;
        };

        if (!empty($districtCode)) {
            // ── Single district: merged PRS + DESE by year ──
            $org = Database::fetch(
                "SELECT id, org_code, org_name FROM organizations WHERE org_code = ?", [$districtCode]
            );
            if ($org) {
                $selectedDistrict = $org;

                // PRS by year
                $prsByYear = Database::fetchAll(
                    "SELECT YEAR(filing_date) as yr, COUNT(*) as prs_count
                     FROM prs_cases WHERE org_id = ? AND filing_date IS NOT NULL
                     GROUP BY yr ORDER BY yr",
                    [(int)$org['id']]
                );

                // DESE restraint by year
                $deseByYear = Database::fetchAll(
                    "SELECT r.school_year,
                            SUM(r.total_restraints) as restraints,
                            SUM(r.enrollment) as enrollment
                     FROM restraint_data r
                     JOIN organizations s ON r.org_id = s.id
                     WHERE s.parent_org_id = ?
                     GROUP BY r.school_year ORDER BY r.school_year",
                    [(int)$org['id']]
                );

                // If no school-level, try district-level
                if (empty($deseByYear)) {
                    $deseByYear = Database::fetchAll(
                        "SELECT school_year, total_restraints as restraints, enrollment
                         FROM restraint_data WHERE org_id = ?
                         ORDER BY school_year",
                        [(int)$org['id']]
                    );
                }

                // Merge by year
                $prsMap = [];
                foreach ($prsByYear as $r) { $prsMap[(int)$r['yr']] = (int)$r['prs_count']; }
                $deseMap = [];
                foreach ($deseByYear as $r) { $deseMap[(int)substr($r['school_year'], 0, 4)] = $r; }

                $allYears = array_unique(array_merge(array_keys($prsMap), array_keys($deseMap)));
                sort($allYears);

                foreach ($allYears as $yr) {
                    $mergedData[] = [
                        'year'        => (string)$yr,
                        'prs_count'   => $prsMap[$yr] ?? 0,
                        'restraints'  => (int)($deseMap[$yr]['restraints'] ?? 0),
                        'enrollment'  => (int)($deseMap[$yr]['enrollment'] ?? 0),
                    ];
                }

                // Dual-axis chart
                if (!empty($mergedData)) {
                    $chart = new Chart('cross-ref-dual', 'bar');
                    $chart->setLabels(array_column($mergedData, 'year'));
                    $chart->addDataset('PRS Cases', array_column($mergedData, 'prs_count'), [
                        'backgroundColor' => 'rgba(255,90,31,0.7)',
                        'borderColor'     => '#ff5a1f',
                        'borderWidth'     => 1,
                        'borderRadius'    => 4,
                    ]);
                    $chart->addDataset('Restraints', array_column($mergedData, 'restraints'), [
                        'type'            => 'line',
                        'yAxisID'         => 'y1',
                        'borderColor'     => '#60a5fa',
                        'backgroundColor' => 'rgba(96,165,250,0.1)',
                        'borderWidth'     => 2,
                        'tension'         => 0.3,
                    ]);
                    $chart->setOption('scales.y.title.text', 'PRS Cases');
                    $chart->setOption('scales.y1.position', 'right');
                    $chart->setOption('scales.y1.title.text', 'Restraints');
                    $chart->setOption('scales.y1.title.display', true);
                    $chart->setOption('scales.y1.grid.drawOnChartArea', false);
                    $chart->setHeight(350);
                    $chartHtml = $chart->render();
                }

                // Local correlation
                $prsVals = array_column($mergedData, 'prs_count');
                $restrVals = array_column($mergedData, 'restraints');
                $correlationTable[] = [
                    'metric_x' => 'PRS Cases', 'metric_y' => 'Restraints',
                    'r' => $pearsonR($prsVals, $restrVals),
                    'n' => count($mergedData),
                ];
            }
        } else {
            // ── Statewide scatter: each dot = a district ──
            $scatterRaw = Database::fetchAll(
                "SELECT o.org_code, o.org_name,
                        COUNT(pc.id) as prs_count,
                        COALESCE(dd.students, 0) as enrollment,
                        COALESCE(rs.restraint_total, 0) as restraint_total,
                        COALESCE(disc.students_disciplined, 0) as disciplined,
                        COALESCE(att.chronically_absent_10_pct, 0) as chronic_absent
                 FROM organizations o
                 LEFT JOIN prs_cases pc ON pc.org_id = o.id
                 LEFT JOIN discipline_data dd ON dd.org_id = o.id
                     AND dd.school_year = (SELECT MAX(school_year) FROM discipline_data d2 WHERE d2.org_id = o.id)
                 LEFT JOIN (
                     SELECT s.parent_org_id as dist_id,
                            SUM(r.total_restraints) as restraint_total,
                            SUM(r.enrollment) as enroll_total
                     FROM restraint_data r
                     JOIN organizations s ON r.org_id = s.id
                     GROUP BY s.parent_org_id
                 ) rs ON rs.dist_id = o.id
                 LEFT JOIN discipline_data disc ON disc.org_id = o.id
                     AND disc.school_year = (SELECT MAX(school_year) FROM discipline_data d3 WHERE d3.org_id = o.id)
                 LEFT JOIN attendance_data att ON att.org_id = o.id
                     AND att.school_year = (SELECT MAX(school_year) FROM attendance_data a2 WHERE a2.org_id = o.id)
                 WHERE o.org_type = 'Public School District' AND o.is_active = 1
                   AND COALESCE(dd.students, 0) > 0
                 GROUP BY o.id, o.org_code, o.org_name, dd.students, rs.restraint_total, disc.students_disciplined, att.chronically_absent_10_pct
                 HAVING COUNT(pc.id) > 0"
            );

            // Build scatter points
            $scatterPoints = [];
            foreach ($scatterRaw as $r) {
                $enr = (int)$r['enrollment'];
                $scatterPoints[] = [
                    'name'           => $r['org_name'],
                    'code'           => $r['org_code'],
                    'prs_rate'       => $enr > 0 ? round(((int)$r['prs_count']) * 1000.0 / $enr, 2) : 0,
                    'restraint_rate' => $enr > 0 ? round(((int)$r['restraint_total']) * 1000.0 / $enr, 2) : 0,
                    'discipline_pct' => $enr > 0 ? round(((int)$r['disciplined']) * 100.0 / $enr, 1) : 0,
                    'chronic_absent' => (float)$r['chronic_absent'],
                    'prs_count'      => (int)$r['prs_count'],
                    'enrollment'     => $enr,
                ];
            }

            // ── Correlation coefficients ──
            $prsRates = $restrRates = $discPcts = $chronicAbs = [];

            foreach ($scatterPoints as $p) {
                $prsRates[]    = $p['prs_rate'];
                $restrRates[]  = $p['restraint_rate'];
                $discPcts[]    = $p['discipline_pct'];
                $chronicAbs[]  = $p['chronic_absent'];
            }

            $correlationTable = [
                ['metric_x' => 'PRS Filing Rate',   'metric_y' => 'Restraint Rate',    'r' => $pearsonR($prsRates, $restrRates),  'n' => count($scatterPoints)],
                ['metric_x' => 'PRS Filing Rate',   'metric_y' => 'Discipline Rate',   'r' => $pearsonR($prsRates, $discPcts),    'n' => count($scatterPoints)],
                ['metric_x' => 'PRS Filing Rate',   'metric_y' => 'Chronic Absentee',  'r' => $pearsonR($prsRates, $chronicAbs),  'n' => count($scatterPoints)],
                ['metric_x' => 'Restraint Rate',    'metric_y' => 'Discipline Rate',   'r' => $pearsonR($restrRates, $discPcts),   'n' => count($scatterPoints)],
            ];

            // Scatter chart: PRS rate vs Restraint rate
            if (!empty($scatterPoints)) {
                $scatterDataset = [];
                foreach ($scatterPoints as $p) {
                    $scatterDataset[] = ['x' => $p['restraint_rate'], 'y' => $p['prs_rate']];
                }

                $sc = new Chart('cross-ref-scatter', 'scatter');
                $sc->setLabels([]);
                $sc->addDataset('Districts', $scatterDataset, [
                    'backgroundColor'   => '#ff5a1f',
                    'borderColor'       => '#ff5a1f',
                    'pointRadius'       => 5,
                    'pointHoverRadius'  => 8,
                ]);
                $sc->setOption('scales.x.title.text', 'Restraints per 1,000 Students');
                $sc->setOption('scales.x.title.display', true);
                $sc->setOption('scales.y.title.text', 'PRS Filings per 1,000 Students');
                $sc->setOption('scales.y.title.display', true);
                $sc->setHeight(380);
                $chartHtml = $sc->render();
            }
        }

        View::render('prs/cross-ref', [
            'page_title'         => 'PRS — DESE Cross-Reference',
            'page_description'   => 'Correlation analysis between PRS filings and DESE-reported data.',
            'page_stylesheet'    => 'data',
            'districts'          => $districts,
            'selectedDistrict'   => $selectedDistrict,
            'districtCode'       => $districtCode,
            'chartHtml'          => $chartHtml,
            'correlationTable'   => $correlationTable,
            'mergedData'         => $mergedData,
            'scatterPoints'      => $scatterPoints ?? [],
        ]);
    }


    // ──────────────────────────────────────────────
    //  API ENDPOINTS
    // ──────────────────────────────────────────────

    /** GET /api/prs/cases — paginated case list JSON */
    public function casesApi(array $params = []): void
    {
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 25)));
        $offset  = ($page - 1) * $perPage;

        $where    = 'WHERE 1=1';
        $bindings = [];

        if (!empty($_GET['status'])) {
            $where .= ' AND pc.current_status = ?';
            $bindings[] = $_GET['status'];
        }
        if (!empty($_GET['category'])) {
            $where .= ' AND EXISTS (SELECT 1 FROM prs_findings pf WHERE pf.prs_case_id = pc.id AND pf.allegation_category = ?)';
            $bindings[] = $_GET['category'];
        }
        if (!empty($_GET['district'])) {
            $where .= ' AND o.org_code = ?';
            $bindings[] = $_GET['district'];
        }
        if (!empty($_GET['year'])) {
            $where .= ' AND YEAR(pc.filing_date) = ?';
            $bindings[] = (int)$_GET['year'];
        }
        if (!empty($_GET['q'])) {
            $where .= ' AND (pc.prs_number LIKE ? OR pc.case_title LIKE ?)';
            $kw = '%' . $_GET['q'] . '%';
            $bindings[] = $kw;
            $bindings[] = $kw;
        }
        $order = 'ORDER BY pc.filing_date DESC';
        if (!empty($_GET['sort'])) {
            $sortMap = [
                'filing_date'       => 'ORDER BY pc.filing_date DESC',
                'filing_date_asc'   => 'ORDER BY pc.filing_date ASC',
                'days_open'         => 'ORDER BY pc.total_days_open DESC',
                'status'            => 'ORDER BY pc.current_status ASC',
            ];
            $order = $sortMap[$_GET['sort']] ?? $order;
        }

        $total = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM prs_cases pc LEFT JOIN organizations o ON pc.org_id = o.id $where",
            $bindings
        );

        $queryBindings = array_merge($bindings, [$perPage, $offset]);
        $data = Database::fetchAll(
            "SELECT pc.id, pc.prs_number, pc.case_title, pc.current_status, pc.filing_date,
                    pc.closure_date, pc.total_days_open, pc.resolution_type,
                    o.org_name, o.org_code
             FROM prs_cases pc
             LEFT JOIN organizations o ON pc.org_id = o.id
             $where
             $order
             LIMIT ? OFFSET ?",
            $queryBindings
        );

        json_response([
            'data' => $data,
            'pagination' => [
                'total'       => $total,
                'page'        => $page,
                'per_page'    => $perPage,
                'total_pages' => (int)ceil($total / $perPage),
            ],
        ]);
    }

    /** GET /api/prs/cases/{id} — full case detail JSON */
    public function caseDetailApi(array $params = []): void
    {
        $id = (int)($params['id'] ?? 0);

        $case = Database::fetch(
            "SELECT pc.*, o.org_name, o.org_code
             FROM prs_cases pc
             LEFT JOIN organizations o ON pc.org_id = o.id
             WHERE pc.id = ?",
            [$id]
        );

        if (!$case) {
            json_response(['error' => 'Case not found'], 404);
            return;
        }

        $events = Database::fetchAll(
            "SELECT * FROM prs_events WHERE prs_case_id = ? ORDER BY event_date ASC, id ASC", [$id]
        );

        $findings = Database::fetchAll(
            "SELECT * FROM prs_findings WHERE prs_case_id = ? ORDER BY finding_number ASC", [$id]
        );

        $documents = Database::fetchAll(
            "SELECT d.id, d.title, d.file_name, d.file_url, d.file_mime, d.document_date,
                    dl.link_type
             FROM documents d
             JOIN document_links dl ON d.id = dl.doc_id
             WHERE dl.target_type = 'prs_case' AND dl.target_id = ?
             ORDER BY d.document_date DESC",
            [$id]
        );

        $case['allegations'] = json_decode($case['allegations'] ?? '[]', true);

        json_response([
            'case'      => $case,
            'events'    => $events,
            'findings'  => $findings,
            'documents' => $documents,
        ]);
    }

    /** GET /api/prs/analytics — pre-aggregated analytics JSON */
    public function analyticsApi(array $params = []): void
    {
        $type = $_GET['type'] ?? 'category_breakdown';
        $district = $_GET['district'] ?? null;

        $result = [];

        switch ($type) {
            case 'category_breakdown':
                $result = Database::fetchAll(
                    "SELECT allegation_category as label, COUNT(*) as value
                     FROM prs_findings
                     WHERE allegation_category IS NOT NULL
                     GROUP BY allegation_category
                     ORDER BY value DESC LIMIT 15"
                );
                break;

            case 'status_distribution':
                $result = Database::fetchAll(
                    "SELECT current_status as label, COUNT(*) as value
                     FROM prs_cases
                     GROUP BY current_status
                     ORDER BY FIELD(current_status, 'filed','accepted','investigating','findings','closed','appealed')"
                );
                break;

            case 'timeline_trends':
                $sql = "SELECT YEAR(filing_date) as label, COUNT(*) as value
                        FROM prs_cases WHERE filing_date IS NOT NULL";
                $binds = [];
                if ($district) {
                    $sql .= " AND org_id = (SELECT id FROM organizations WHERE org_code = ?)";
                    $binds[] = $district;
                }
                $result = Database::fetchAll("$sql GROUP BY label ORDER BY label", $binds);
                break;

            case 'resolution_rates':
                $result = Database::fetchAll(
                    "SELECT resolution_type as label, COUNT(*) as value
                     FROM prs_cases
                     WHERE resolution_type IS NOT NULL
                     GROUP BY resolution_type
                     ORDER BY value DESC"
                );
                break;

            case 'deadline_compliance':
                $result = [
                    ['label' => 'On Time', 'value' => (int)Database::fetchColumn(
                        "SELECT COUNT(*) FROM prs_cases WHERE statutory_deadline IS NOT NULL AND actual_resolution_date IS NOT NULL AND actual_resolution_date <= statutory_deadline"
                    )],
                    ['label' => 'Overdue', 'value' => (int)Database::fetchColumn(
                        "SELECT COUNT(*) FROM prs_cases WHERE statutory_deadline IS NOT NULL AND actual_resolution_date IS NOT NULL AND actual_resolution_date > statutory_deadline"
                    )],
                ];
                break;

            case 'district_volume':
                $result = Database::fetchAll(
                    "SELECT o.org_name as label, COUNT(*) as value
                     FROM prs_cases pc
                     JOIN organizations o ON pc.org_id = o.id
                     GROUP BY o.id, o.org_name
                     ORDER BY value DESC LIMIT 20"
                );
                break;

            case 'year_over_year':
                $raw = Database::fetchAll(
                    "SELECT YEAR(filing_date) as yr, COUNT(*) as cnt
                     FROM prs_cases WHERE filing_date IS NOT NULL
                     GROUP BY yr ORDER BY yr"
                );
                $prev = 0;
                foreach ($raw as $row) {
                    $result[] = [
                        'label' => (string)$row['yr'],
                        'value' => (int)$row['cnt'],
                        'change_pct' => $prev > 0 ? round(100 * ($row['cnt'] - $prev) / $prev, 1) : 0,
                    ];
                    $prev = (int)$row['cnt'];
                }
                break;
        }

        json_response($result);
    }

    /** GET /api/prs/timeline — chronological events for a case */
    public function timelineApi(array $params = []): void
    {
        $caseId = (int)($_GET['case_id'] ?? 0);
        if ($caseId <= 0) {
            json_response(['error' => 'case_id required'], 400);
        }

        $events = Database::fetchAll(
            "SELECT id, event_date, event_type, event_description, actor
             FROM prs_events
             WHERE prs_case_id = ?
             ORDER BY event_date ASC, id ASC",
            [$caseId]
        );

        json_response($events);
    }

    /** GET /api/prs/cross-ref — PRS + DESE metrics for a district or statewide */
    public function crossRefApi(array $params = []): void
    {
        $districtCode = $_GET['district'] ?? '';
        $metrics = array_filter(explode(',', $_GET['metrics'] ?? 'restraint,discipline'));

        // ── Statewide mode: return per-district scatter data ──
        if (empty($districtCode)) {
            $rows = Database::fetchAll(
                "SELECT o.org_code, o.org_name,
                        COUNT(pc.id) as prs_count,
                        COALESCE(dd.students, 0) as enrollment,
                        COALESCE(rs.restraint_total, 0) as restraint_total,
                        COALESCE(disc.students_disciplined, 0) as disciplined,
                        COALESCE(att.chronic_absent_pct, 0) as chronic_absent
                 FROM organizations o
                 LEFT JOIN prs_cases pc ON pc.org_id = o.id
                 LEFT JOIN discipline_data dd ON dd.org_id = o.id
                     AND dd.school_year = (SELECT MAX(school_year) FROM discipline_data d2 WHERE d2.org_id = o.id)
                 LEFT JOIN (
                     SELECT s.parent_org_id as dist_id,
                            SUM(r.total_restraints) as restraint_total
                     FROM restraint_data r
                     JOIN organizations s ON r.org_id = s.id
                     GROUP BY s.parent_org_id
                 ) rs ON rs.dist_id = o.id
                 LEFT JOIN discipline_data disc ON disc.org_id = o.id
                     AND disc.school_year = (SELECT MAX(school_year) FROM discipline_data d3 WHERE d3.org_id = o.id)
                 LEFT JOIN attendance_data att ON att.org_id = o.id
                     AND att.school_year = (SELECT MAX(school_year) FROM attendance_data a2 WHERE a2.org_id = o.id)
                 WHERE o.org_type = 'Public School District' AND o.is_active = 1
                   AND COALESCE(dd.students, 0) > 0
                 GROUP BY o.id, o.org_code, o.org_name, dd.students, rs.restraint_total, disc.students_disciplined, att.chronic_absent_pct
                 HAVING COUNT(pc.id) > 0
                 ORDER BY o.org_name"
            );

            $result = [];
            foreach ($rows as $r) {
                $enr = (int)$r['enrollment'];
                $result[] = [
                    'org_code'       => $r['org_code'],
                    'org_name'       => $r['org_name'],
                    'prs_count'      => (int)$r['prs_count'],
                    'prs_rate'       => $enr > 0 ? round(((int)$r['prs_count']) * 1000.0 / $enr, 2) : 0,
                    'restraint_total'=> (int)$r['restraint_total'],
                    'restraint_rate' => $enr > 0 ? round(((int)$r['restraint_total']) * 1000.0 / $enr, 2) : 0,
                    'disciplined'    => (int)$r['disciplined'],
                    'discipline_pct' => $enr > 0 ? round(((int)$r['disciplined']) * 100.0 / $enr, 1) : 0,
                    'chronic_absent' => (float)$r['chronic_absent'],
                    'enrollment'     => $enr,
                ];
            }
            json_response(['count' => count($result), 'districts' => $result]);
            return;
        }

        $org = Database::fetch(
            "SELECT id, org_code, org_name FROM organizations WHERE org_code = ?", [$districtCode]
        );
        if (!$org) {
            json_response(['error' => 'District not found'], 404);
        }

        $result = [
            'district' => $org,
            'prs'      => [
                'total_cases'   => (int)Database::fetchColumn("SELECT COUNT(*) FROM prs_cases WHERE org_id = ?", [(int)$org['id']]),
                'open_cases'    => (int)Database::fetchColumn("SELECT COUNT(*) FROM prs_cases WHERE org_id = ? AND current_status IN ('filed','accepted','investigating','findings')", [(int)$org['id']]),
                'substantiated' => (int)Database::fetchColumn("SELECT COUNT(*) FROM prs_cases WHERE org_id = ? AND resolution_type = 'substantiated'", [(int)$org['id']]),
                'avg_resolution_days' => (int)Database::fetchColumn("SELECT ROUND(AVG(total_days_open)) FROM prs_cases WHERE org_id = ? AND closure_date IS NOT NULL AND total_days_open IS NOT NULL", [(int)$org['id']]),
            ],
            'dese' => [],
        ];

        foreach ($metrics as $metric) {
            switch ($metric) {
                case 'restraint':
                    $result['dese']['restraint'] = Database::fetchAll(
                        "SELECT r.school_year, SUM(r.total_restraints) as total, SUM(r.enrollment) as enrollment
                         FROM restraint_data r
                         JOIN organizations o ON r.org_id = o.id
                         WHERE o.parent_org_id = ?
                         GROUP BY r.school_year ORDER BY r.school_year DESC LIMIT 5",
                        [(int)$org['id']]
                    );
                    if (empty($result['dese']['restraint'])) {
                        $result['dese']['restraint'] = Database::fetchAll(
                            "SELECT school_year, total_restraints as total, enrollment
                             FROM restraint_data WHERE org_id = ?
                             ORDER BY school_year DESC LIMIT 5",
                            [(int)$org['id']]
                        );
                    }
                    break;

                case 'discipline':
                    $result['dese']['discipline'] = Database::fetchAll(
                        "SELECT school_year, students, students_disciplined,
                                pct_in_school_susp, pct_out_school_susp, pct_expulsion
                         FROM discipline_data WHERE org_id = ?
                         ORDER BY school_year DESC LIMIT 5",
                        [(int)$org['id']]
                    );
                    break;

                case 'sped':
                    $result['dese']['sped'] = Database::fetchAll(
                        "SELECT school_year, sped_grad_rate, sped_dropout_rate, lre_full_incl_pct
                         FROM sped_results WHERE org_id = ?
                         ORDER BY school_year DESC LIMIT 5",
                        [(int)$org['id']]
                    );
                    break;

                case 'enrollment':
                    $result['dese']['enrollment'] = Database::fetchAll(
                        "SELECT school_year, sped_pct, el_pct, low_income_pct, high_needs_pct
                         FROM enrollment_data WHERE org_id = ?
                         ORDER BY school_year DESC LIMIT 5",
                        [(int)$org['id']]
                    );
                    break;

                case 'attendance':
                    $result['dese']['attendance'] = Database::fetchAll(
                        "SELECT school_year, attendance_rate, chronically_absent_10_pct as chronic_absent_pct
                         FROM attendance_data WHERE org_id = ?
                         ORDER BY school_year DESC LIMIT 5",
                        [(int)$org['id']]
                    );
                    break;
            }
        }

        json_response($result);
    }

    // ──────────────────────────────────────────────
    //  DATA QUALITY (called from admin)
    // ──────────────────────────────────────────────

    /** Return quality issues array */
    public static function qualityChecks(): array
    {
        $issues = [];

        // Orphan events
        $orphans = Database::fetchAll(
            "SELECT pe.id, pe.event_date, pe.event_type
             FROM prs_events pe
             LEFT JOIN prs_cases pc ON pe.prs_case_id = pc.id
             WHERE pc.id IS NULL"
        );
        if (!empty($orphans)) {
            $issues[] = [
                'type'        => 'orphan_events',
                'label'       => 'Orphan Events (no matching prs_case)',
                'count'       => count($orphans),
                'severity'    => 'high',
                'description' => 'Events referencing deleted or invalid prs_case_id.',
            ];
        }

        // Orphan findings
        $orphanFindings = Database::fetchAll(
            "SELECT pf.id, pf.allegation_category
             FROM prs_findings pf
             LEFT JOIN prs_cases pc ON pf.prs_case_id = pc.id
             WHERE pc.id IS NULL"
        );
        if (!empty($orphanFindings)) {
            $issues[] = [
                'type'        => 'orphan_findings',
                'label'       => 'Orphan Findings (no matching prs_case)',
                'count'       => count($orphanFindings),
                'severity'    => 'high',
                'description' => 'Findings referencing deleted or invalid prs_case_id.',
            ];
        }

        // Missing deadlines (findings issued but no statutory deadline)
        $missingDeadlines = Database::fetchColumn(
            "SELECT COUNT(*) FROM prs_cases WHERE findings_issued_date IS NOT NULL AND statutory_deadline IS NULL"
        );
        if ((int)$missingDeadlines > 0) {
            $issues[] = [
                'type'        => 'missing_deadlines',
                'label'       => 'Cases with Findings but No Statutory Deadline',
                'count'       => (int)$missingDeadlines,
                'severity'    => 'medium',
                'description' => 'These cases have findings_issued_date set but no statutory_deadline. Compliance cannot be tracked.',
            ];
        }

        // Date inconsistencies (findings before filing)
        $dateIssues = Database::fetchColumn(
            "SELECT COUNT(*) FROM prs_cases WHERE findings_issued_date IS NOT NULL AND filing_date IS NOT NULL AND findings_issued_date < filing_date"
        );
        if ((int)$dateIssues > 0) {
            $issues[] = [
                'type'        => 'date_inconsistency',
                'label'       => 'Date Inconsistencies (findings before filing)',
                'count'       => (int)$dateIssues,
                'severity'    => 'medium',
                'description' => 'Cases where findings_issued_date precedes filing_date.',
            ];
        }

        // Open cases past statutory deadline
        $overdue = Database::fetchColumn(
            "SELECT COUNT(*) FROM prs_cases WHERE current_status NOT IN ('closed','appealed') AND statutory_deadline IS NOT NULL AND statutory_deadline < CURDATE()"
        );
        if ((int)$overdue > 0) {
            $issues[] = [
                'type'        => 'overdue_cases',
                'label'       => 'Open Cases Past Statutory Deadline',
                'count'       => (int)$overdue,
                'severity'    => 'high',
                'description' => 'Cases still open but past their statutory deadline.',
            ];
        }

        // Cases without any events
        $noEvents = Database::fetchColumn(
            "SELECT COUNT(*) FROM prs_cases pc WHERE NOT EXISTS (SELECT 1 FROM prs_events pe WHERE pe.prs_case_id = pc.id)"
        );
        if ((int)$noEvents > 0) {
            $issues[] = [
                'type'        => 'no_events',
                'label'       => 'Cases Without Any Events',
                'count'       => (int)$noEvents,
                'severity'    => 'low',
                'description' => 'Cases with no timeline events recorded.',
            ];
        }

        return $issues;
    }

    /** GET /prs/map — county-level PRS choropleth map */
    public function map(array $params = []): void
    {
        $countyData = Database::fetchAllCached(
            "SELECT c.slug, c.county_name,
                    COUNT(pc.id) AS total_cases,
                    COUNT(CASE WHEN pc.current_status != 'closed' THEN 1 END) AS open_cases
             FROM counties c
             LEFT JOIN organizations o ON o.county_id = c.id
             LEFT JOIN prs_cases pc ON pc.org_id = o.id
             GROUP BY c.id, c.slug, c.county_name
             ORDER BY c.county_name",
            [], 300
        );

        $caseCounts = [];
        foreach ($countyData as $row) { $caseCounts[] = (int)($row['total_cases'] ?? 0); }
        sort($caseCounts);
        $n = count($caseCounts);
        $p33 = $n > 0 ? $caseCounts[(int)($n * 0.33)] : 0;
        $p66 = $n > 0 ? $caseCounts[(int)($n * 0.66)] : 0;
        $min = $caseCounts[0] ?? 0;
        $max = $caseCounts[$n-1] ?? 1;
        $range = $max - $min ?: 1;

        $colors = [];
        foreach ($countyData as $row) {
            $cases = (int)($row['total_cases'] ?? 0);
            $dcolor = $cases <= $p33 ? '#22c55e' : ($cases <= $p66 ? '#f59e0b' : '#ef4444');
            $ratio = ($cases - $min) / $range;
            $hue = round(120 * (1 - $ratio));
            $scolor = "hsl({$hue}, 70%, 45%)";
            $name = str_replace(' (county)', '', $row['county_name']);
            $colors[$row['slug']] = [
                'color' => $dcolor, 'smooth' => $scolor,
                'name' => $name, 'cases' => $cases,
                'open' => (int)($row['open_cases'] ?? 0),
            ];
        }

        View::render('prs/map', [
            'page_title'      => 'PRS County Map',
            'page_stylesheet' => 'data',
            'county_colors'   => $colors,
        ]);
    }

    /** GET /prs/town-map — town-level PRS choropleth */
    public function townMap(array $params = []): void
    {
        $townData = Database::fetchAllCached(
            "SELECT o.town, COUNT(pc.id) AS total_cases,
                    COUNT(CASE WHEN pc.current_status != 'closed' THEN 1 END) AS open_cases
             FROM organizations o
             LEFT JOIN prs_cases pc ON pc.org_id = o.id
             WHERE o.town IS NOT NULL AND o.town != '' AND o.is_active = 1
             GROUP BY o.town
             ORDER BY o.town",
            [], 300
        );

        $counts = [];
        foreach ($townData as $row) {
            $c = (int)($row['total_cases'] ?? 0);
            if ($c > 0) $counts[] = $c;
        }
        sort($counts);
        $n = count($counts);
        $p33 = $n > 0 ? $counts[(int)($n * 0.33)] : 0;
        $p66 = $n > 0 ? $counts[(int)($n * 0.66)] : 0;

        $colors = [];
        foreach ($townData as $row) {
            $cases = (int)($row['total_cases'] ?? 0);
            if ($cases == 0) {
                $color = '#2a2a2a';
            } else {
                $color = $cases <= $p33 ? '#22c55e' : ($cases <= $p66 ? '#f59e0b' : '#ef4444');
            }
            $slug = strtolower(str_replace([' ', "'", '.'], '-', $row['town']));
            $colors[$slug] = [
                'color' => $color,
                'name' => $row['town'],
                'cases' => $cases,
                'open' => (int)($row['open_cases'] ?? 0),
            ];
        }

        View::render('prs/town-map', [
            'page_title'      => 'PRS Town Map',
            'page_stylesheet' => 'data',
            'town_colors'     => $colors,
        ]);
    }

    /** GET /prs/calendar — daily filing calendar heatmap */
    public function calendar(array $params = []): void
    {
        $calData = Database::fetchAllCached(
            "SELECT DATE(filing_date) as d, COUNT(*) as cnt
             FROM prs_cases WHERE filing_date IS NOT NULL
             GROUP BY DATE(filing_date) ORDER BY d",
            [], 300
        );
        $byDay = []; $maxCnt = 0;
        foreach ($calData as $r) { $byDay[$r['d']] = (int)$r['cnt']; $maxCnt = max($maxCnt, (int)$r['cnt']); }
        $years = range(2016, (int)date('Y'));
        $year = (int)($_GET['year'] ?? date('Y'));

        View::render('prs/calendar', [
            'page_title'      => 'PRS Filing Calendar',
            'page_stylesheet' => 'data',
            'byDay'           => $byDay,
            'maxCnt'          => max($maxCnt, 1),
            'years'           => $years,
            'year'            => $year,
        ]);
    }
}
