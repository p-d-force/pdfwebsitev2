<?php declare(strict_types=1);
namespace App\Controllers;

use App\Core\View;
use App\Core\Database;

class SchoolController
{
    private const SCHOOL_TYPES = "'Public School','Charter School','Collaborative School','Approved Special Education School'";

    /** GET /schools — paginated list with filters */
    public function list(array $params = []): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = DEFAULT_PER_PAGE;
        $offset = ($page - 1) * $perPage;

        // ── Filters ──
        $districtFilter = $_GET['district'] ?? '';
        $gradeFilter    = $_GET['grade'] ?? '';
        $typeFilter     = $_GET['type'] ?? '';
        $sort           = $_GET['sort'] ?? 'name';
        $order          = ($_GET['order'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

        $where  = "WHERE s.org_type IN (" . self::SCHOOL_TYPES . ") AND s.is_active = 1";
        $bind   = [];

        if (!empty($districtFilter)) {
            $where .= " AND d.org_code = ?";
            $bind[] = $districtFilter;
        }
        if (!empty($gradeFilter)) {
            switch ($gradeFilter) {
                case 'PK-5':
                    $where .= " AND (s.grade_span LIKE 'PK%' OR s.grade_span LIKE 'K%')";
                    break;
                case '6-8':
                    $where .= " AND (s.grade_span LIKE '%06%' OR s.grade_span LIKE '%07%' OR s.grade_span LIKE '%08%')";
                    break;
                case '9-12':
                    $where .= " AND (s.grade_span LIKE '%09%' OR s.grade_span LIKE '%10%' OR s.grade_span LIKE '%11%' OR s.grade_span LIKE '%12%')";
                    break;
            }
        }
        if (!empty($typeFilter)) {
            $where .= " AND s.org_type = ?";
            $bind[] = $typeFilter;
        }

        // Count
        $total = (int)Database::fetchColumn(
            "SELECT COUNT(*)
             FROM organizations s
             LEFT JOIN organizations d ON s.parent_org_id = d.id
             $where",
            $bind
        );

        // Sort column mapping
        $sortCols = [
            'name'           => 'd.org_name, s.org_name',
            'enrollment'     => 'COALESCE(r.enrollment, 0)',
            'restraint_rate' => 'COALESCE(r.total_restraints, 0)',
        ];
        $orderCol = $sortCols[$sort] ?? $sortCols['name'];

        // Paginated school list
        $latestYear = Database::fetchColumn("SELECT MAX(school_year) FROM restraint_data");

        $queryBind = array_merge($bind, [$perPage, $offset]);
        $schools = Database::fetchAll(
            "SELECT s.org_code, s.org_name, s.org_type, s.town, s.grade_span,
                    d.org_name AS district_name, d.org_code AS district_code,
                    r.enrollment, r.students_restrained, r.total_restraints, r.total_injuries
             FROM organizations s
             LEFT JOIN organizations d ON s.parent_org_id = d.id
             LEFT JOIN restraint_data r ON r.org_id = s.id AND r.school_year = ?
             $where
             ORDER BY $orderCol $order
             LIMIT ? OFFSET ?",
            array_merge([$latestYear], $queryBind)
        );

        // Districts for filter dropdown
        $districts = Database::fetchAllCached(
            "SELECT DISTINCT d.org_code, d.org_name
             FROM organizations d
             JOIN organizations s ON s.parent_org_id = d.id
             WHERE s.org_type IN (" . self::SCHOOL_TYPES . ") AND s.is_active = 1
             ORDER BY d.org_name",
            [],
            300
        );

        $pagination = paginate($total, $perPage);

        // Build query string for pagination (preserve filters, exclude page)
        $qsParams = $_GET;
        unset($qsParams['page']);
        $qs = !empty($qsParams) ? '?' . http_build_query($qsParams) : '';

        View::render('schools/list', [
            'page_title'        => 'Schools',
            'page_description'  => 'Browse all ' . number_format($total) . ' Massachusetts public schools. View restraint data, enrollment, and demographics by school.',
            'page_stylesheet'   => 'schools',
            'schools'           => $schools,
            'pagination'        => $pagination,
            'districts'         => $districts,
            'total'             => $total,
            'qs'                => $qs,
            'filters'           => [
                'district' => $districtFilter,
                'grade'    => $gradeFilter,
                'type'     => $typeFilter,
                'sort'     => $sort,
                'order'    => $_GET['order'] ?? 'asc',
            ],
        ]);
    }


    // ═══════════════════════════════════════════════
    // School Comparison
    // ═══════════════════════════════════════════════

    /** GET /schools/compare — side-by-side school comparison with radar chart */
    public function compare(array $params = []): void
    {
        $codes = array_filter(explode(',', $_GET['codes'] ?? ''));
        $codes = array_map('strtoupper', array_slice($codes, 0, 5)); // max 5 schools, normalize case

        $schools = [];
        $radarLabels = [];
        $radarDatasets = [];
        $bestPerformer = [];

        if (!empty($codes)) {
            $placeholders = implode(',', array_fill(0, count($codes), '?'));
            $latestYear = Database::fetchColumn("SELECT MAX(school_year) FROM restraint_data");

            $rows = Database::fetchAll(
                "SELECT s.org_code, s.org_name, s.org_type, s.town, s.grade_span,
                        s.title_1_status, s.parent_org_id,
                        d.org_name AS district_name, d.org_code AS district_code,
                        r.enrollment, r.students_restrained, r.total_restraints, r.total_injuries
                 FROM organizations s
                 JOIN organizations d ON s.parent_org_id = d.id
                 LEFT JOIN restraint_data r ON r.org_id = s.id AND r.school_year = ?
                 WHERE s.org_code IN ($placeholders)
                   AND s.org_type IN (" . self::SCHOOL_TYPES . ")
                   AND s.is_active = 1
                 ORDER BY s.org_name",
                array_merge([$latestYear], array_values($codes))
            );

            // Index by org_code
            $byCode = [];
            foreach ($rows as $row) {
                $byCode[$row['org_code']] = $row;
            }

            // Reorder to match input codes
            foreach ($codes as $code) {
                $code = strtoupper($code);
                if (isset($byCode[$code])) {
                    $schools[] = $byCode[$code];
                }
            }

            // Compute rates
            foreach ($schools as &$s) {
                $enr = max(1, (int)($s['enrollment'] ?? 1));
                $s['restraint_rate'] = round(((int)($s['total_restraints'] ?? 0) / $enr) * 100, 2);
                $s['injury_rate'] = round(((int)($s['total_injuries'] ?? 0) / $enr) * 100, 2);
                $s['students_restrained_pct'] = round(((int)($s['students_restrained'] ?? 0) / $enr) * 100, 2);
            }
            unset($s);

            // District SPED % for radar
            if (!empty($schools)) {
                $districtIds = array_column($schools, 'parent_org_id');
                $distPlaceholders = implode(',', array_fill(0, count($districtIds), '?'));
                $spedRows = Database::fetchAll(
                    "SELECT org_id, sped_pct
                     FROM enrollment_data
                     WHERE org_id IN ($distPlaceholders)
                       AND school_year = ?
                     ORDER BY org_id",
                    array_merge(array_values($districtIds), [$latestYear])
                );
                $spedByOrg = [];
                foreach ($spedRows as $sr) {
                    $spedByOrg[$sr['org_id']] = (float)($sr['sped_pct'] ?? 0);
                }
                foreach ($schools as &$s) {
                    $s['district_sped_pct'] = $spedByOrg[$s['parent_org_id']] ?? 0;
                }
                unset($s);
            }

            // Radar chart data (5 axes)
            // Axes: Restraint Rate (invert - lower is better), Students Restrained %, Injuries Rate, Enrollment, SPED %
            $radarLabels = ['Restraint Rate', 'Students Restrained %', 'Injuries Rate', 'Enrollment', 'District SPED %'];

            // Normalize values for radar (0-100 scale)
            $maxVals = ['restraint_rate' => 1, 'students_restrained_pct' => 1, 'injury_rate' => 1, 'enrollment' => 1, 'district_sped_pct' => 1];
            foreach ($schools as $s) {
                $maxVals['restraint_rate'] = max($maxVals['restraint_rate'], $s['restraint_rate'] ?? 0);
                $maxVals['students_restrained_pct'] = max($maxVals['students_restrained_pct'], $s['students_restrained_pct'] ?? 0);
                $maxVals['injury_rate'] = max($maxVals['injury_rate'], $s['injury_rate'] ?? 0);
                $maxVals['enrollment'] = max($maxVals['enrollment'], (int)($s['enrollment'] ?? 1));
                $maxVals['district_sped_pct'] = max($maxVals['district_sped_pct'], $s['district_sped_pct'] ?? 0);
            }

            $palette = ['#ff5a1f', '#60a5fa', '#22c55e', '#f59e0b', '#ef4444'];
            $bestPerformer = [
                'restraint_rate'   => ['value' => PHP_FLOAT_MAX, 'name' => ''],
                'injury_rate'      => ['value' => PHP_FLOAT_MAX, 'name' => ''],
                'students_restrained_pct' => ['value' => PHP_FLOAT_MAX, 'name' => ''],
            ];

            foreach ($schools as $i => $s) {
                $vals = [
                    $maxVals['restraint_rate'] > 0 ? round(($s['restraint_rate'] / $maxVals['restraint_rate']) * 100, 1) : 0,
                    $maxVals['students_restrained_pct'] > 0 ? round(($s['students_restrained_pct'] / $maxVals['students_restrained_pct']) * 100, 1) : 0,
                    $maxVals['injury_rate'] > 0 ? round((($s['injury_rate'] ?? 0) / $maxVals['injury_rate']) * 100, 1) : 0,
                    $maxVals['enrollment'] > 0 ? round(((int)($s['enrollment'] ?? 1) / $maxVals['enrollment']) * 100, 1) : 0,
                    $maxVals['district_sped_pct'] > 0 ? round(($s['district_sped_pct'] / $maxVals['district_sped_pct']) * 100, 1) : 0,
                ];

                $radarDatasets[] = [
                    'label'           => $s['org_name'],
                    'data'            => $vals,
                    'backgroundColor' => $palette[$i % 5] . '33',
                    'borderColor'     => $palette[$i % 5],
                    'borderWidth'     => 2,
                    'pointBackgroundColor' => $palette[$i % 5],
                ];

                // Track best performers (lower is better for these)
                foreach (['restraint_rate', 'injury_rate', 'students_restrained_pct'] as $metric) {
                    if (($s[$metric] ?? PHP_FLOAT_MAX) < $bestPerformer[$metric]['value']) {
                        $bestPerformer[$metric] = ['value' => $s[$metric], 'name' => $s['org_name']];
                    }
                }
            }
        }

        View::render('schools/compare', [
            'page_title'       => 'School Comparison',
            'page_description' => 'Compare restraint data across Massachusetts public schools side-by-side.',
            'page_stylesheet'  => 'schools',
            'schools'          => $schools,
            'codes'            => $codes,
            'radar_labels'     => $radarLabels,
            'radar_datasets'   => $radarDatasets,
            'best_performer'   => $bestPerformer,
        ]);
    }

    // ═══════════════════════════════════════════════
    // School Rankings
    // ═══════════════════════════════════════════════

    /** GET /schools/rankings — sortable leaderboard with grade-span and district filters */
    public function rankings(array $params = []): void
    {
        $page       = max(1, (int)($_GET['page'] ?? 1));
        $perPage    = min(100, max(10, (int)($_GET['per_page'] ?? 25)));
        $offset     = ($page - 1) * $perPage;
        $sort       = $_GET['sort'] ?? 'restraint_rate';
        $order      = ($_GET['order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
        $grade      = $_GET['grade'] ?? '';
        $district   = $_GET['district'] ?? '';
        $view       = $_GET['view'] ?? ''; // 'top10' or 'bottom10'

        $latestYear = Database::fetchColumn("SELECT MAX(school_year) FROM restraint_data");

        $where  = "WHERE s.org_type IN (" . self::SCHOOL_TYPES . ") AND s.is_active = 1 AND r.school_year = ?";
        $bind   = [$latestYear];

        if (!empty($district)) {
            $where .= " AND d.org_code = ?";
            $bind[] = $district;
        }

        // Grade span filter: match patterns like PK-5, 6-8, 9-12
        if (!empty($grade)) {
            if ($grade === 'PK-5') {
                $where .= " AND (s.grade_span LIKE 'PK%' OR s.grade_span LIKE 'K%')";
            } elseif ($grade === '6-8') {
                $where .= " AND (s.grade_span LIKE '%06%' OR s.grade_span LIKE '%07%' OR s.grade_span LIKE '%08%')";
            } elseif ($grade === '9-12') {
                $where .= " AND (s.grade_span LIKE '%09%' OR s.grade_span LIKE '%10%' OR s.grade_span LIKE '%11%' OR s.grade_span LIKE '%12%')";
            }
        }

        // Sort column mapping
        $sortCols = [
            'name'           => 's.org_name',
            'district'       => 'd.org_name',
            'enrollment'     => 'r.enrollment',
            'restraint_rate' => '(COALESCE(r.total_restraints,0) / NULLIF(r.enrollment,0) * 100)',
            'attendance'     => 'a.attendance_rate',
        ];
        $orderCol = $sortCols[$sort] ?? $sortCols['restraint_rate'];

        // Top/Bottom toggle
        $limitClause = '';
        if ($view === 'top10') {
            $limitClause = 'LIMIT 10';
            $order = 'DESC';
        } elseif ($view === 'bottom10') {
            $limitClause = 'LIMIT 10';
            $order = 'ASC';
        }
        if (empty($limitClause)) {
            $limitClause = "LIMIT ? OFFSET ?";
        }

        $countBind = $bind;
        $total = (int)Database::fetchColumn(
            "SELECT COUNT(*)
             FROM organizations s
             JOIN organizations d ON s.parent_org_id = d.id
             JOIN restraint_data r ON r.org_id = s.id
             LEFT JOIN attendance_data a ON a.org_id = s.parent_org_id AND a.school_year = ?
             $where",
            array_merge([$latestYear], $countBind)
        );

        $queryBind = empty($limitClause) || str_contains($limitClause, 'OFFSET')
            ? array_merge($bind, [$perPage, $offset])
            : $bind;

        $rankings = Database::fetchAll(
            "SELECT s.org_code, s.org_name, s.org_type, s.grade_span, s.town,
                    d.org_name AS district_name, d.org_code AS district_code,
                    r.enrollment, r.total_restraints, r.students_restrained, r.total_injuries,
                    COALESCE(r.total_restraints,0) / NULLIF(r.enrollment,0) * 100 AS restraint_rate,
                    a.attendance_rate
             FROM organizations s
             JOIN organizations d ON s.parent_org_id = d.id
             JOIN restraint_data r ON r.org_id = s.id
             LEFT JOIN attendance_data a ON a.org_id = s.parent_org_id AND a.school_year = ?
             $where
             ORDER BY $orderCol $order
             $limitClause",
            array_merge([$latestYear], $queryBind)
        );

        // Compute rank numbers
        $startRank = $offset + 1;
        foreach ($rankings as $i => &$r) {
            $r['rank'] = $startRank + $i;
            $r['restraint_rate'] = round((float)$r['restraint_rate'], 2);
            $r['attendance_rate'] = isset($r['attendance_rate']) ? round((float)$r['attendance_rate'], 1) : null;
        }
        unset($r);

        // Districts for filter dropdown
        $districts = Database::fetchAllCached(
            "SELECT DISTINCT d.org_code, d.org_name
             FROM organizations d
             JOIN organizations s ON s.parent_org_id = d.id
             WHERE s.org_type IN (" . self::SCHOOL_TYPES . ") AND s.is_active = 1
             ORDER BY d.org_name",
            [], 300
        );

        $pagination = paginate($total, $perPage);

        $qsParams = $_GET;
        unset($qsParams['page']);
        $qs = !empty($qsParams) ? '?' . http_build_query($qsParams) : '';

        // School name map for search dropdown (top 200 by enrollment)
        $schoolNames = Database::fetchAllCached(
            "SELECT s.org_code, s.org_name, d.org_name AS district_name
             FROM organizations s
             JOIN organizations d ON s.parent_org_id = d.id
             JOIN restraint_data r ON r.org_id = s.id AND r.school_year = ?
             WHERE s.org_type IN (" . self::SCHOOL_TYPES . ") AND s.is_active = 1
             ORDER BY r.enrollment DESC
             LIMIT 200",
            [$latestYear], 300
        );

        View::render('schools/rankings', [
            'page_title'       => 'School Rankings',
            'page_description' => 'Massachusetts public school rankings by restraint rate, enrollment, and attendance.',
            'page_stylesheet'  => 'schools',
            'rankings'         => $rankings,
            'total'            => $total,
            'pagination'       => $pagination,
            'qs'               => $qs,
            'filters'          => ['sort' => $sort, 'order' => $order, 'grade' => $grade, 'district' => $district, 'view' => $view],
            'districts'        => $districts,
            'school_names'     => $schoolNames,
            'latest_year'      => $latestYear,
        ]);
    }

    // ═══════════════════════════════════════════════
    // School Equity Analysis
    // ═══════════════════════════════════════════════

    /** GET /schools/equity — restraint and discipline disparity analysis */
    public function equity(array $params = []): void
    {
        $latestYear = Database::fetchColumn("SELECT MAX(school_year) FROM restraint_data");

        // ── SPED restraint disparity ──
        // For each school: restraint rate vs its district's SPED %
        $spedDisparity = Database::fetchAll(
            "SELECT s.org_code, s.org_name, s.grade_span, s.town,
                    d.org_name AS district_name, d.org_code AS district_code,
                    r.enrollment, r.total_restraints,
                    COALESCE(r.total_restraints,0) / NULLIF(r.enrollment,0) * 100 AS restraint_rate,
                    e.sped_pct AS district_sped_pct
             FROM organizations s
             JOIN organizations d ON s.parent_org_id = d.id
             JOIN restraint_data r ON r.org_id = s.id AND r.school_year = ?
             JOIN enrollment_data e ON e.org_id = s.parent_org_id AND e.school_year = ?
             WHERE s.org_type IN (" . self::SCHOOL_TYPES . ")
               AND s.is_active = 1
               AND r.enrollment > 0
               AND e.sped_pct IS NOT NULL
             ORDER BY r.total_restraints DESC",
            [$latestYear, $latestYear]
        );

        foreach ($spedDisparity as &$row) {
            $row['restraint_rate'] = round((float)$row['restraint_rate'], 2);
            $row['district_sped_pct'] = round((float)$row['district_sped_pct'], 1);
        }
        unset($row);

        // ── Low-income discipline disparity ──
        // District-level: discipline rate vs low-income %
        $discDisparity = Database::fetchAll(
            "SELECT d.org_code, d.org_name,
                    e.low_income_pct,
                    disc.students_disciplined, disc.students,
                    COALESCE(disc.students_disciplined,0) / NULLIF(disc.students,0) * 100 AS discipline_rate
             FROM organizations d
             JOIN discipline_data disc ON disc.org_id = d.id AND disc.school_year = ?
             JOIN enrollment_data e ON e.org_id = d.id AND e.school_year = ?
             WHERE d.org_type = 'Public School District'
               AND d.is_active = 1
               AND e.low_income_pct IS NOT NULL
               AND disc.students > 0
             ORDER BY disc.students_disciplined DESC",
            [$latestYear, $latestYear]
        );

        foreach ($discDisparity as &$row) {
            $row['discipline_rate'] = round((float)$row['discipline_rate'], 2);
            $row['low_income_pct'] = round((float)$row['low_income_pct'], 1);
        }
        unset($row);

        // ── Title I vs non-Title I restraint comparison ──
        $title1Comparison = Database::fetchAll(
            "SELECT
                CASE
                    WHEN s.title_1_status LIKE '%schoolwide%' OR s.title_1_status LIKE '%targeted%' THEN 'Title I'
                    ELSE 'Non-Title I'
                END AS category,
                COUNT(*) AS school_count,
                ROUND(AVG(COALESCE(r.total_restraints,0) / NULLIF(r.enrollment,0) * 100), 2) AS avg_restraint_rate,
                SUM(r.total_restraints) AS total_restraints,
                SUM(r.enrollment) AS total_enrollment
             FROM organizations s
             JOIN restraint_data r ON r.org_id = s.id AND r.school_year = ?
             WHERE s.org_type IN (" . self::SCHOOL_TYPES . ")
               AND s.is_active = 1
               AND r.enrollment > 0
             GROUP BY category
             ORDER BY category",
            [$latestYear]
        );

        // ── Charter vs Traditional comparison ──
        $charterComparison = Database::fetchAll(
            "SELECT
                CASE WHEN s.org_type = 'Charter School' THEN 'Charter' ELSE 'Traditional Public' END AS category,
                COUNT(*) AS school_count,
                ROUND(AVG(COALESCE(r.total_restraints,0) / NULLIF(r.enrollment,0) * 100), 2) AS avg_restraint_rate,
                SUM(r.total_restraints) AS total_restraints,
                SUM(r.enrollment) AS total_enrollment
             FROM organizations s
             JOIN restraint_data r ON r.org_id = s.id AND r.school_year = ?
             WHERE s.org_type IN (" . self::SCHOOL_TYPES . ")
               AND s.is_active = 1
               AND r.enrollment > 0
             GROUP BY category
             ORDER BY category",
            [$latestYear]
        );

        // ── Compute state averages for reference ──
        $stateAvg = Database::fetch(
            "SELECT
                ROUND(AVG(COALESCE(r.total_restraints,0) / NULLIF(r.enrollment,0) * 100), 2) AS avg_restraint_rate,
                ROUND(AVG(e.sped_pct), 1) AS avg_sped_pct,
                ROUND(AVG(e.low_income_pct), 1) AS avg_low_income_pct
             FROM organizations s
             JOIN restraint_data r ON r.org_id = s.id AND r.school_year = ?
             JOIN enrollment_data e ON e.org_id = s.parent_org_id AND e.school_year = ?
             WHERE s.org_type IN (" . self::SCHOOL_TYPES . ")
               AND s.is_active = 1
               AND r.enrollment > 0",
            [$latestYear, $latestYear]
        );

        View::render('schools/equity', [
            'page_title'         => 'School Equity Analysis',
            'page_description'   => 'Analyze restraint and discipline disparities across Massachusetts schools by SPED status, income, and school type.',
            'page_stylesheet'    => 'schools',
            'sped_disparity'     => $spedDisparity,
            'disc_disparity'     => $discDisparity,
            'title1_comparison'  => $title1Comparison,
            'charter_comparison' => $charterComparison,
            'state_avg'          => $stateAvg,
            'latest_year'        => $latestYear,
        ]);
    }

    // ═══════════════════════════════════════════════
    // School Trends
    // ═══════════════════════════════════════════════

    /** GET /schools/trends — multi-year trend lines with district/state comparison */
    public function trends(array $params = []): void
    {
        $selectedCode = $_GET['code'] ?? '';

        $schoolData = null;
        $districtTrend = [];
        $stateTrend = [];

        // School list for dropdown (top 200)
        $latestYear = Database::fetchColumn("SELECT MAX(school_year) FROM restraint_data");
        $schoolNames = Database::fetchAllCached(
            "SELECT s.org_code, s.org_name, d.org_name AS district_name
             FROM organizations s
             JOIN organizations d ON s.parent_org_id = d.id
             JOIN restraint_data r ON r.org_id = s.id AND r.school_year = ?
             WHERE s.org_type IN (" . self::SCHOOL_TYPES . ") AND s.is_active = 1
             ORDER BY r.enrollment DESC
             LIMIT 200",
            [$latestYear], 300
        );

        if (!empty($selectedCode)) {
            $code = strtoupper($selectedCode);
            $school = Database::fetch(
                "SELECT s.*, d.org_name AS district_name, d.org_code AS district_code, d.id AS district_id
                 FROM organizations s
                 JOIN organizations d ON s.parent_org_id = d.id
                 WHERE s.org_code = ?
                   AND s.org_type IN (" . self::SCHOOL_TYPES . ")
                   AND s.is_active = 1",
                [$code]
            );

            if ($school) {
                // School trend data
                $schoolData = Database::fetchAll(
                    "SELECT school_year, enrollment, students_restrained, total_restraints, total_injuries,
                            COALESCE(total_restraints,0) / NULLIF(enrollment,0) * 100 AS restraint_rate
                     FROM restraint_data
                     WHERE org_id = ?
                     ORDER BY school_year",
                    [$school['id']]
                );

                // District aggregate trend (all schools in the district, per year)
                $districtTrend = Database::fetchAll(
                    "SELECT r.school_year,
                            SUM(r.enrollment) AS enrollment,
                            SUM(r.total_restraints) AS total_restraints,
                            COALESCE(SUM(r.total_restraints),0) / NULLIF(SUM(r.enrollment),0) * 100 AS restraint_rate
                     FROM restraint_data r
                     JOIN organizations s ON r.org_id = s.id
                     WHERE s.parent_org_id = ?
                       AND s.org_type IN (" . self::SCHOOL_TYPES . ")
                       AND s.is_active = 1
                     GROUP BY r.school_year
                     ORDER BY r.school_year",
                    [$school['district_id']]
                );

                // State aggregate trend (all schools, per year)
                $stateTrend = Database::fetchAll(
                    "SELECT r.school_year,
                            SUM(r.enrollment) AS enrollment,
                            SUM(r.total_restraints) AS total_restraints,
                            COALESCE(SUM(r.total_restraints),0) / NULLIF(SUM(r.enrollment),0) * 100 AS restraint_rate
                     FROM restraint_data r
                     JOIN organizations s ON r.org_id = s.id
                     WHERE s.org_type IN (" . self::SCHOOL_TYPES . ")
                       AND s.is_active = 1
                     GROUP BY r.school_year
                     ORDER BY r.school_year",
                    []
                );

                // Index district and state by year for easy lookup
                $districtByYear = [];
                foreach ($districtTrend as $d) {
                    $districtByYear[$d['school_year']] = $d;
                }
                $stateByYear = [];
                foreach ($stateTrend as $s) {
                    $stateByYear[$s['school_year']] = $s;
                }

                $schoolData = array_map(function ($sd) use ($districtByYear, $stateByYear) {
                    $sd['restraint_rate'] = round((float)$sd['restraint_rate'], 2);
                    $sd['district_restraint_rate'] = isset($districtByYear[$sd['school_year']])
                        ? round((float)$districtByYear[$sd['school_year']]['restraint_rate'], 2) : null;
                    $sd['state_restraint_rate'] = isset($stateByYear[$sd['school_year']])
                        ? round((float)$stateByYear[$sd['school_year']]['restraint_rate'], 2) : null;
                    return $sd;
                }, $schoolData);
            }
        }

        View::render('schools/trends', [
            'page_title'       => 'School Trends',
            'page_description' => 'Multi-year trend analysis for Massachusetts public schools — compare individual schools against district and state averages.',
            'page_stylesheet'  => 'schools',
            'school_names'     => $schoolNames,
            'selected_code'    => $selectedCode,
            'school'           => $school ?? null,
            'school_data'      => $schoolData,
            'district_trend'   => $districtTrend,
            'state_trend'      => $stateTrend,
        ]);
    }

    // ═══════════════════════════════════════════════
    // Data Integrity Dashboard
    // ═══════════════════════════════════════════════

    /** GET /schools/integrity — data quality checks */
    public function integrity(array $params = []): void
    {
        $issues = [];
        $latestYear = Database::fetchColumn("SELECT MAX(school_year) FROM restraint_data");

        // 1 — Schools without parent_org_id
        $noParent = Database::fetchAll(
            "SELECT org_code, org_name, org_type, town
             FROM organizations
             WHERE org_type IN (" . self::SCHOOL_TYPES . ")
               AND is_active = 1
               AND parent_org_id IS NULL
             ORDER BY org_name"
        );
        if (!empty($noParent)) {
            $issues[] = ['id' => 'schools_without_parent', 'label' => 'Schools without a parent district', 'severity' => 'warning', 'count' => count($noParent), 'rows' => $noParent];
        }

        // 2 — Schools with restraint data but enrollment = 0 / NULL
        if ($latestYear) {
            $noEnrollment = Database::fetchAll(
                "SELECT s.org_code, s.org_name, s.town, r.school_year, r.total_restraints, r.enrollment
                 FROM restraint_data r
                 JOIN organizations s ON r.org_id = s.id
                 WHERE s.org_type IN (" . self::SCHOOL_TYPES . ")
                   AND s.is_active = 1
                   AND r.school_year = ?
                   AND r.total_restraints > 0
                   AND (r.enrollment IS NULL OR r.enrollment = 0)
                 ORDER BY r.total_restraints DESC
                 LIMIT 100",
                [$latestYear]
            );
            if (!empty($noEnrollment)) {
                $issues[] = ['id' => 'restraints_no_enrollment', 'label' => 'Restraints but enrollment = 0 (can\'t compute rate)', 'severity' => 'error', 'count' => count($noEnrollment), 'rows' => $noEnrollment];
            }
        }

        // 3 — Districts with 0 active schools
        $noSchools = Database::fetchAll(
            "SELECT d.org_code, d.org_name, d.town
             FROM organizations d
             LEFT JOIN organizations s ON s.parent_org_id = d.id
               AND s.org_type IN (" . self::SCHOOL_TYPES . ")
               AND s.is_active = 1
             WHERE d.org_type = 'Public School District'
               AND d.is_active = 1
             GROUP BY d.id
             HAVING COUNT(s.id) = 0
             ORDER BY d.org_name"
        );
        if (!empty($noSchools)) {
            $issues[] = ['id' => 'districts_no_schools', 'label' => 'Districts with no active schools', 'severity' => 'warning', 'count' => count($noSchools), 'rows' => $noSchools];
        }

        // 4 — Orphan restraint_data rows (org_id not in organizations)
        $orphans = Database::fetchAll(
            "SELECT r.org_id, r.school_year, r.total_restraints, r.enrollment
             FROM restraint_data r
             LEFT JOIN organizations o ON r.org_id = o.id
             WHERE o.id IS NULL
             ORDER BY r.total_restraints DESC
             LIMIT 100"
        );
        if (!empty($orphans)) {
            $issues[] = ['id' => 'orphan_restraint_data', 'label' => 'Orphan restraint_data rows (organization deleted)', 'severity' => 'error', 'count' => count($orphans), 'rows' => $orphans];
        }

        // 5 — Schools with enrollment > 0 but restraint_count not matching student count
        if ($latestYear) {
            $mismatch = Database::fetchAll(
                "SELECT s.org_code, s.org_name, r.enrollment, r.total_restraints, r.students_restrained
                 FROM restraint_data r
                 JOIN organizations s ON r.org_id = s.id
                 WHERE s.org_type IN (" . self::SCHOOL_TYPES . ")
                   AND s.is_active = 1
                   AND r.school_year = ?
                   AND r.enrollment > 0
                   AND r.total_restraints > 0
                   AND r.students_restrained = 0
                 ORDER BY r.total_restraints DESC
                 LIMIT 100",
                [$latestYear]
            );
            if (!empty($mismatch)) {
                $issues[] = ['id' => 'restraints_no_students', 'label' => 'Restraints reported but students_restrained = 0', 'severity' => 'warning', 'count' => count($mismatch), 'rows' => $mismatch];
            }
        }

        $totalIssues = array_sum(array_column($issues, 'count'));

        View::render('schools/integrity', [
            'page_title'       => 'Data Integrity Check',
            'page_stylesheet'  => 'schools',
            'issues'           => $issues,
            'total_issues'     => $totalIssues,
            'checked_at'       => date('Y-m-d H:i:s'),
        ]);
    }


    // ═══════════════════════════════════════════════
    // School Explorer — interactive scatter plot
    // ═══════════════════════════════════════════════

    /** GET /schools/explore — interactive scatter plot with axis selectors, year slider, filters */
    public function explore(array $params = []): void
    {
        // Fetch available years
        $years = Database::fetchAllCached(
            "SELECT DISTINCT school_year FROM restraint_data ORDER BY school_year",
            [],
            300
        );

        // Fetch districts for filter
        $districts = Database::fetchAllCached(
            "SELECT DISTINCT d.org_name, d.org_code
             FROM organizations d
             WHERE d.org_type = 'Public School District' AND d.is_active = 1
             ORDER BY d.org_name",
            [],
            300
        );

        // Fetch all school-grade data for the scatter (latest year, all schools)
        $latestYear = Database::fetchColumn(
            "SELECT MAX(school_year) FROM restraint_data"
        );

        $schoolTypes = self::SCHOOL_TYPES;
        $schools = Database::fetchAllCached(
            "SELECT s.org_name, s.org_code, s.grade_span, s.town,
                    d.org_name AS district_name, d.org_code AS district_code,
                    r.school_year,
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
             WHERE s.org_type IN ({$schoolTypes})
               AND s.is_active = 1
               AND r.enrollment > 0
             ORDER BY d.org_name, s.org_name, r.school_year",
            [],
            300
        );

        // Pre-compute rates
        foreach ($schools as &$s) {
            $enr = max(1, (int)($s['enrollment'] ?? 0));
            $s['restraint_rate'] = round(((int)($s['total_restraints'] ?? 0) / $enr) * 100, 2);
            $s['discipline_rate'] = round((float)($s['pct_out_school_susp'] ?? 0), 2);
            $s['attendance_pct'] = round((float)($s['attendance_rate'] ?? 0), 1);
        }
        unset($s);

        // Insights from latest year only
        $schoolsLatest = array_filter($schools, fn($s) => ($s['school_year'] ?? '') === $latestYear);
        $insights = $this->generateExploreInsights(array_values($schoolsLatest));


        View::render('schools/explore', [
            'page_title'       => 'School Explorer — Interactive Scatter Plot',
            'page_description' => 'Explore Massachusetts schools with an interactive scatter plot. Compare enrollment, restraint rates, demographics, and more across districts.',
            'page_stylesheet'  => 'schools',
            'years'            => array_column($years, 'school_year'),
            'latest_year'      => $latestYear,
            'districts'        => $districts,
            'schools'          => $schools,
            'insights'         => $insights,
        ]);
    }

    // ═══════════════════════════════════════════════
    // School Report Card — printable summary
    // ═══════════════════════════════════════════════

    /** GET /schools/{slug}/report-card — printable single-page school summary */
    public function reportCard(array $params = []): void
    {
        $code = strtoupper($params['slug'] ?? '');
        $school = Database::fetch(
            "SELECT s.*, d.org_name AS district_name, d.org_code AS district_code
             FROM organizations s
             LEFT JOIN organizations d ON s.parent_org_id = d.id
             WHERE s.org_code = ?
               AND s.org_type IN (" . self::SCHOOL_TYPES . ")
               AND s.is_active = 1",
            [$code]
        );

        if (!$school) {
            \http_response_code(404);
            View::render('errors/404', [
                'page_title'       => 'School Not Found',
                'page_stylesheet'  => 'home',
            ]);
            return;
        }

        // Restraint data (all years)
        $restraint = Database::fetchAll(
            "SELECT school_year, enrollment, students_restrained, total_restraints, total_injuries
             FROM restraint_data
             WHERE org_id = ?
             ORDER BY school_year",
            [$school['id']]
        );

        // Latest year restraint
        $latestRestraint = !empty($restraint) ? $restraint[count($restraint) - 1] : null;
        $latestYear = $latestRestraint['school_year'] ?? null;

        // Demographics from parent district
        $demographics = null;
        if (!empty($school['parent_org_id'])) {
            $demographics = Database::fetch(
                "SELECT sped_pct, el_pct, low_income_pct, high_needs_pct, school_year
                 FROM enrollment_data
                 WHERE org_id = ?
                 ORDER BY school_year DESC
                 LIMIT 1",
                [$school['parent_org_id']]
            );
        }

        // State average for comparison
        $stateAvgRate = null;
        if ($latestYear) {
            $stateRow = Database::fetch(
                "SELECT SUM(r.total_restraints) AS total_r, SUM(r.enrollment) AS total_e
                 FROM restraint_data r
                 JOIN organizations s ON r.org_id = s.id
                 WHERE s.org_type IN (" . self::SCHOOL_TYPES . ")
                   AND s.is_active = 1
                   AND r.school_year = ?
                   AND r.enrollment > 0",
                [$latestYear]
            );
            if ($stateRow && ($stateRow['total_e'] > 0)) {
                $stateAvgRate = round(($stateRow['total_r'] / $stateRow['total_e']) * 100, 2);
            }
        }

        // School restraint rate
        $schoolRestraintRate = null;
        if ($latestRestraint) {
            $enr = max(1, (int)($latestRestraint['enrollment'] ?? 1));
            $schoolRestraintRate = round(((int)($latestRestraint['total_restraints'] ?? 0) / $enr) * 100, 2);
        }

        // District comparison
        $districtRestraintTotal = null;
        $districtEnrollPct = null;
        $districtRestraintPct = null;
        if (!empty($school['parent_org_id']) && $latestYear) {
            $districtRestraintTotal = (int)Database::fetchColumn(
                "SELECT SUM(r.total_restraints)
                 FROM restraint_data r
                 JOIN organizations s ON r.org_id = s.id
                 WHERE s.parent_org_id = ? AND r.school_year = ?",
                [$school['parent_org_id'], $latestYear]
            );

            if ($latestRestraint) {
                $schoolEnroll = max(1, (int)($latestRestraint['enrollment'] ?? 0));
                $districtEnroll = (int)Database::fetchColumn(
                    "SELECT SUM(r.enrollment)
                     FROM restraint_data r
                     JOIN organizations s ON r.org_id = s.id
                     WHERE s.parent_org_id = ? AND r.school_year = ?",
                    [$school['parent_org_id'], $latestYear]
                );
                if ($districtEnroll > 0) {
                    $districtEnrollPct = round(($schoolEnroll / $districtEnroll) * 100, 1);
                }
                $schoolRestr = (int)($latestRestraint['total_restraints'] ?? 0);
                if ($districtRestraintTotal > 0) {
                    $districtRestraintPct = round(($schoolRestr / $districtRestraintTotal) * 100, 1);
                }
            }
        }

        // Auto-generated insights
        $insights = [];
        if ($schoolRestraintRate !== null && $stateAvgRate !== null) {
            if ($schoolRestraintRate > $stateAvgRate) {
                $diff = round($schoolRestraintRate - $stateAvgRate, 1);
                $pctHigher = $stateAvgRate > 0 ? round(($diff / $stateAvgRate) * 100, 1) : 0;
                $insights[] = "This school's restraint rate is {$diff} points ({$pctHigher}%) higher than the state average of {$stateAvgRate}%.";
            } else {
                $diff = round($stateAvgRate - $schoolRestraintRate, 1);
                $insights[] = "This school's restraint rate is {$diff} points below the state average of {$stateAvgRate}%.";
            }
        }

        // Trend insight
        if (count($restraint) >= 2) {
            $first = $restraint[0];
            $last = $restraint[count($restraint) - 1];
            $firstEnr = max(1, (int)($first['enrollment'] ?? 1));
            $lastEnr = max(1, (int)($last['enrollment'] ?? 1));
            $firstRate = round(((int)($first['total_restraints'] ?? 0) / $firstEnr) * 100, 2);
            $lastRate = round(((int)($last['total_restraints'] ?? 0) / $lastEnr) * 100, 2);
            $change = round($lastRate - $firstRate, 2);
            if ($change < 0) {
                $insights[] = "Restraint rate decreased by " . abs($change) . " points from {$first['school_year']} ({$firstRate}%) to {$last['school_year']} ({$lastRate}%).";
            } elseif ($change > 0) {
                $insights[] = "Restraint rate increased by {$change} points from {$first['school_year']} ({$firstRate}%) to {$last['school_year']} ({$lastRate}%).";
            }
        }

        // Students restrained insight
        if ($latestRestraint && ($latestRestraint['students_restrained'] ?? 0) > 0) {
            $enr = max(1, (int)($latestRestraint['enrollment'] ?? 1));
            $pctRestrained = round(((int)$latestRestraint['students_restrained'] / $enr) * 100, 1);
            $insights[] = number_format($latestRestraint['students_restrained']) . " students (" . $pctRestrained . "% of enrollment) were restrained in {$latestYear}.";
        }

        View::render('schools/report-card', [
            'page_title'                => 'Report Card: ' . $school['org_name'],
            'page_description'          => 'Printable report card for ' . $school['org_name'] . ' — restraint data, demographics, and district comparison.',
            'page_stylesheet'           => 'schools',
            'school'                    => $school,
            'restraint'                 => $restraint,
            'latest_restraint'          => $latestRestraint,
            'latest_year'               => $latestYear,
            'demographics'              => $demographics,
            'state_avg_rate'            => $stateAvgRate,
            'school_restraint_rate'     => $schoolRestraintRate,
            'district_restraint_total'  => $districtRestraintTotal,
            'district_enroll_pct'       => $districtEnrollPct,
            'district_restraint_pct'    => $districtRestraintPct,
            'insights'                  => $insights,
        ]);
    }

    /**
     * Generate auto-discovered insights from the explorer dataset.
     * @param array $schools
     * @return string[]
     */
    private function generateExploreInsights(array $schools): array
    {
        $insights = [];
        if (empty($schools)) return $insights;

        // Compute state averages
        $totalEnroll = 0;
        $totalRestraints = 0;
        $rates = [];
        foreach ($schools as $s) {
            $enr = (int)($s['enrollment'] ?? 0);
            $restr = (int)($s['total_restraints'] ?? 0);
            if ($enr > 0) {
                $totalEnroll += $enr;
                $totalRestraints += $restr;
                $rates[] = round(($restr / $enr) * 100, 2);
            }
        }

        if ($totalEnroll > 0) {
            $avgRate = round(($totalRestraints / $totalEnroll) * 100, 2);
            sort($rates);
            $medianRate = $rates[(int)(count($rates) / 2)] ?? $avgRate;
            $maxRate = end($rates);

            $insights[] = "The statewide average restraint rate across " . number_format(count($schools)) . " schools is {$avgRate}%.";
            $insights[] = "The median school has a restraint rate of {$medianRate}%. The highest rate is {$maxRate}%.";

            // High-restraint schools
            $highThreshold = $avgRate * 2;
            $highCount = count(array_filter($rates, fn($r) => $r > $highThreshold));
            if ($highCount > 0) {
                $insights[] = number_format($highCount) . " schools have a restraint rate more than double the state average (> {$highThreshold}%).";
            }

            // Zero restraint schools
            $zeroCount = count(array_filter($rates, fn($r) => $r === 0.0));
            if ($zeroCount > 0) {
                $insights[] = number_format($zeroCount) . " schools reported zero restraints.";
            }
        }

        return $insights;
    }

    /** GET /schools/{slug} — single school detail */
    public function show(array $params = []): void
    {
        $code = strtoupper($params['slug'] ?? '');
        $school = Database::fetch(
            "SELECT s.*, d.org_name AS district_name, d.org_code AS district_code
             FROM organizations s
             LEFT JOIN organizations d ON s.parent_org_id = d.id
             WHERE s.org_code = ?
               AND s.org_type IN (" . self::SCHOOL_TYPES . ")
               AND s.is_active = 1",
            [$code]
        );

        if (!$school) {
            \http_response_code(404);
            View::render('errors/404', [
                'page_title'       => 'School Not Found',
                'page_stylesheet'  => 'home',
            ]);
            return;
        }

        // Restraint data (all years)
        $restraint = Database::fetchAll(
            "SELECT school_year, enrollment, students_restrained, total_restraints, total_injuries
             FROM restraint_data
             WHERE org_id = ?
             ORDER BY school_year",
            [$school['id']]
        );

        // Demographics from parent district's enrollment_data (latest year)
        $demographics = null;
        if (!empty($school['parent_org_id'])) {
            $demographics = Database::fetch(
                "SELECT sped_pct, el_pct, low_income_pct, high_needs_pct, school_year
                 FROM enrollment_data
                 WHERE org_id = ?
                 ORDER BY school_year DESC
                 LIMIT 1",
                [$school['parent_org_id']]
            );
        }

        // District restraint total for comparison
        $districtRestraintTotal = null;
        $latestYear = !empty($restraint) ? $restraint[count($restraint) - 1]['school_year'] : null;
        $stateAvgRate = null;
        $percentileRank = null;

        if (!empty($school['parent_org_id']) && !empty($restraint) && $latestYear) {
            $districtRestraintTotal = (int)Database::fetchColumn(
                "SELECT SUM(r.total_restraints)
                 FROM restraint_data r
                 JOIN organizations s ON r.org_id = s.id
                 WHERE s.parent_org_id = ? AND r.school_year = ?",
                [$school['parent_org_id'], $latestYear]
            );
        }

        // State average restraint rate (all schools, latest year)
        if ($latestYear) {
            $stateAvgRow = Database::fetch(
                "SELECT SUM(r.total_restraints) AS total_r, SUM(r.enrollment) AS total_e
                 FROM restraint_data r
                 JOIN organizations s ON r.org_id = s.id
                 WHERE s.org_type IN (" . self::SCHOOL_TYPES . ")
                   AND s.is_active = 1
                   AND r.school_year = ?
                   AND r.enrollment > 0",
                [$latestYear]
            );
            if ($stateAvgRow && ($stateAvgRow['total_e'] > 0)) {
                $stateAvgRate = round(($stateAvgRow['total_r'] / $stateAvgRow['total_e']) * 100, 2);
            }

            // Percentile rank: count schools with LOWER restraint rate
            $latestEnrollment = !empty($restraint) ? (int)($restraint[count($restraint) - 1]['enrollment'] ?? 1) : 0;
            $latestRestraints = !empty($restraint) ? (int)($restraint[count($restraint) - 1]['total_restraints'] ?? 0) : 0;
            if ($latestEnrollment > 0) {
                $thisRate = $latestRestraints / $latestEnrollment;
                $totalSchools = (int)Database::fetchColumn(
                    "SELECT COUNT(*)
                     FROM restraint_data r
                     JOIN organizations s ON r.org_id = s.id
                     WHERE s.org_type IN (" . self::SCHOOL_TYPES . ")
                       AND s.is_active = 1
                       AND r.school_year = ?
                       AND r.enrollment > 0",
                    [$latestYear]
                );
                $lowerCount = (int)Database::fetchColumn(
                    "SELECT COUNT(*)
                     FROM restraint_data r
                     JOIN organizations s ON r.org_id = s.id
                     WHERE s.org_type IN (" . self::SCHOOL_TYPES . ")
                       AND s.is_active = 1
                       AND r.school_year = ?
                       AND r.enrollment > 0
                       AND (COALESCE(r.total_restraints,0) / r.enrollment) < ?",
                    [$latestYear, $thisRate]
                );
                $percentileRank = $totalSchools > 0 ? round(($lowerCount / $totalSchools) * 100, 1) : null;
            }
        }

        // ── District aggregate: % of enrollment & % of restraints ──
        $districtEnrollPct = null;
        $districtRestraintPct = null;
        $pctRating = '';

        if (!empty($school['parent_org_id']) && !empty($restraint) && $latestYear) {
            $last = $restraint[count($restraint) - 1];
            $schoolEnroll = max(1, (int)($last['enrollment'] ?? 0));
            $schoolRestr  = (int)($last['total_restraints'] ?? 0);

            $siblings = Database::fetchAll(
                "SELECT s.id, r.enrollment, r.total_restraints
                 FROM organizations s
                 JOIN restraint_data r ON r.org_id = s.id AND r.school_year = ?
                 WHERE s.parent_org_id = ?
                   AND s.org_type IN (" . self::SCHOOL_TYPES . ")
                   AND s.is_active = 1",
                [$latestYear, $school['parent_org_id']]
            );

            $distEnrollment = 0;
            $distRestraints = 0;
            foreach ($siblings as $sib) {
                $distEnrollment += max(0, (int)($sib['enrollment'] ?? 0));
                $distRestraints += max(0, (int)($sib['total_restraints'] ?? 0));
            }

            if ($distEnrollment > 0) {
                $districtEnrollPct = round(($schoolEnroll / $distEnrollment) * 100, 1);
            }
            if ($distRestraints > 0) {
                $districtRestraintPct = round(($schoolRestr / $distRestraints) * 100, 1);
            }

            if ($districtEnrollPct !== null && $districtRestraintPct !== null) {
                if ($districtRestraintPct <= $districtEnrollPct) {
                    $pctRating = 'good';
                } elseif ($districtRestraintPct > $districtEnrollPct * 1.5) {
                    $pctRating = 'bad';
                }
            }
        }

        // ── Demographic context: school (district-level) vs state ──
        $demoContext = null;
        if ($latestYear) {
            // State averages from enrollment_data (all districts, latest year)
            $stateDemos = Database::fetch(
                "SELECT AVG(sped_pct) AS sped_pct, AVG(el_pct) AS el_pct, AVG(low_income_pct) AS low_income_pct
                 FROM enrollment_data
                 WHERE school_year = ?
                   AND sped_pct IS NOT NULL",
                [$latestYear]
            );
            if ($stateDemos) {
                $demoContext = [
                    'state_sped'        => round((float)($stateDemos['sped_pct'] ?? 0), 1),
                    'state_el'          => round((float)($stateDemos['el_pct'] ?? 0), 1),
                    'state_low_income'  => round((float)($stateDemos['low_income_pct'] ?? 0), 1),
                    'district_sped'     => $demographics ? round((float)($demographics['sped_pct'] ?? 0), 1) : null,
                    'district_el'       => $demographics ? round((float)($demographics['el_pct'] ?? 0), 1) : null,
                    'district_low_income' => $demographics ? round((float)($demographics['low_income_pct'] ?? 0), 1) : null,
                ];
            }
        }

        // School restraint rate for display
        $schoolRestraintRate = null;
        if (!empty($restraint)) {
            $last = $restraint[count($restraint) - 1];
            $enr = max(1, (int)($last['enrollment'] ?? 1));
            $schoolRestraintRate = round(((int)($last['total_restraints'] ?? 0) / $enr) * 100, 2);
        }

        View::render('schools/detail', [
            'page_title'                => $school['org_name'],
            'page_description'          => $school['org_name'] . ' — restraint data, enrollment, and demographics for ' . ($school['town'] ?? 'Massachusetts') . '.',
            'page_stylesheet'           => 'schools',
            'school'                    => $school,
            'restraint'                 => $restraint,
            'demographics'              => $demographics,
            'district_restraint_total'  => $districtRestraintTotal,
            'state_avg_rate'            => $stateAvgRate,
            'school_restraint_rate'     => $schoolRestraintRate,
            'percentile_rank'           => $percentileRank,
            'latest_year'               => $latestYear,
            'district_enroll_pct'       => $districtEnrollPct,
            'district_restraint_pct'    => $districtRestraintPct,
            'pct_rating'                => $pctRating,
            'demo_context'              => $demoContext,
        ]);
    }
}
