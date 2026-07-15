<?php declare(strict_types=1);
namespace App\Controllers;

use App\Core\View;
use App\Core\Database;
use App\Components\Chart;

/**
 * Data sub-page controllers — paginated browsers with rich charts for each DESE dataset.
 */
class DataSubController
{
    /** GET /data/restraint */
    public function restraint(array $params = []): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $schoolYear = $_GET['school_year'] ?? null;
        $districtCode = $_GET['district'] ?? null;

        $where = "WHERE 1=1";
        $bindings = [];

        if ($schoolYear) { $where .= " AND r.school_year = ?"; $bindings[] = $schoolYear; }
        if ($districtCode) {
            $where .= " AND o.org_code = ?";
            $bindings[] = $districtCode;
        }

        $total = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM restraint_data r JOIN organizations o ON r.org_id = o.id $where", $bindings
        );

        $queryBindings = array_merge($bindings, [$perPage, $offset]);
        $rows = Database::fetchAll(
            "SELECT o.org_name as district_name, r.school_name, r.school_year,
                    r.enrollment, r.students_restrained, r.total_restraints,
                    r.total_injuries, r.total_restraints_suppressed
             FROM restraint_data r
             JOIN organizations o ON r.org_id = o.id
             $where
             ORDER BY o.org_name, r.school_name
             LIMIT ? OFFSET ?",
            $queryBindings
        );

        $schoolYears = Database::fetchAll("SELECT DISTINCT school_year FROM restraint_data ORDER BY school_year DESC");
        $districts = Database::fetchAllCached(
            "SELECT DISTINCT o.org_code, o.org_name FROM restraint_data r JOIN organizations o ON r.org_id = o.id ORDER BY o.org_name",
            [], 300
        );

        // ── Charts ──
        $chartsHtml = '';
        $extraHtml = '';
        $stateAverages = [];
        $sparklineData = null;
        $columnKeys = [];

        // State averages for heatmap
        if ($schoolYear) {
            $avgs = Database::fetchAllCached(
                "SELECT AVG(total_restraints) AS avg_restraints, AVG(total_injuries) AS avg_injuries,
                        AVG(enrollment) AS avg_enrollment, AVG(students_restrained) AS avg_students
                 FROM restraint_data WHERE school_year = ?",
                [$schoolYear], 300
            );
            if ($avgs && $avgs[0]) {
                $stateAverages = [
                    'total_restraints' => (float)($avgs[0]['avg_restraints'] ?? 0),
                    'total_injuries' => (float)($avgs[0]['avg_injuries'] ?? 0),
                    'enrollment' => (float)($avgs[0]['avg_enrollment'] ?? 0),
                    'students_restrained' => (float)($avgs[0]['avg_students'] ?? 0),
                ];
            }
            $columnKeys = ['total_restraints', 'total_injuries', 'enrollment', 'students_restrained'];
        }

        // 1. Restraint trends dual-axis chart
        $trendData = Database::fetchAllCached(
            "SELECT school_year, SUM(total_restraints) AS restraints, SUM(total_injuries) AS injuries
             FROM restraint_data GROUP BY school_year ORDER BY school_year",
            [], 300
        );
        if ($trendData) {
            $labels = array_column($trendData, 'school_year');
            $restraintVals = array_map('intval', array_column($trendData, 'restraints'));
            $injuryVals = array_map('intval', array_column($trendData, 'injuries'));

            $rc = new Chart('restraintMainChart', 'bar');
            $rc->setLabels($labels);
            $rc->addDataset('Total Restraints', $restraintVals, [
                'backgroundColor' => 'rgba(255,90,31,0.6)',
                'borderColor' => 'rgba(255,90,31,0.9)',
                'borderWidth' => 1,
                'borderRadius' => 4,
            ]);
            $rc->addDataset('Injuries', $injuryVals, [
                'type' => 'line',
                'yAxisID' => 'y1',
                'borderColor' => '#f59e0b',
                'borderWidth' => 2,
                'pointRadius' => 4,
                'pointBackgroundColor' => '#f59e0b',
                'tension' => 0.3,
                'backgroundColor' => 'transparent',
            ]);
            $rc->setOption('scales', [
                'y' => ['beginAtZero' => true],
                'y1' => [
                    'position' => 'right',
                    'beginAtZero' => true,
                    'ticks' => ['color' => '#f59e0b'],
                    'grid' => ['display' => false],
                ],
            ]);
            $rc->setHeight(400);

            $chartsHtml .= '<div class="chart-card"><h3>Statewide Restraint Trends</h3>'
                . '<div style="position:relative;height:400px;">' . $rc->render() . '</div></div>';
        }

        // 2. Severity distribution
        $sevYear = $schoolYear ?: date('Y') - 1;
        $sevData = Database::fetchAllCached(
            "SELECT CASE
                WHEN total_restraints = 0 THEN '0'
                WHEN total_restraints BETWEEN 1 AND 5 THEN '1-5'
                WHEN total_restraints BETWEEN 6 AND 20 THEN '6-20'
                WHEN total_restraints BETWEEN 21 AND 50 THEN '21-50'
                WHEN total_restraints BETWEEN 51 AND 100 THEN '51-100'
                ELSE '100+' END AS bucket,
                COUNT(*) AS cnt
             FROM restraint_data WHERE school_year = ?
             GROUP BY bucket
             ORDER BY CASE bucket
                WHEN '0' THEN 1 WHEN '1-5' THEN 2 WHEN '6-20' THEN 3
                WHEN '21-50' THEN 4 WHEN '51-100' THEN 5 ELSE 6 END",
            [$sevYear], 300
        );
        if ($sevData) {
            $sevLabels = array_column($sevData, 'bucket');
            $sevCounts = array_map('intval', array_column($sevData, 'cnt'));

            $sc = new Chart('restraintSevChart', 'bar');
            $sc->setLabels($sevLabels);
            $sc->addDataset('Schools', $sevCounts, ['palette' => 'warm']);
            $sc->setOption('plugins', [
                'legend' => ['display' => false],
                'title' => ['display' => true, 'text' => "Restraint Distribution — $sevYear", 'color' => '#a0a0a0', 'font' => ['family' => 'Inter', 'size' => 13]],
            ]);

            $extraHtml .= '<div class="chart-card"><h3>Restraint Severity Distribution</h3>' . $sc->render() . '</div>';
        }

        // 3. District sparkline
        if ($districtCode && $schoolYear) {
            $sparkRows = Database::fetchAllCached(
                "SELECT r.school_year, SUM(r.total_restraints) AS restraints
                 FROM restraint_data r
                 JOIN organizations s ON r.org_id = s.id
                 JOIN organizations d ON s.parent_org_id = d.id
                 WHERE d.org_code = ?
                 GROUP BY r.school_year ORDER BY r.school_year",
                [$districtCode], 300
            );
            if ($sparkRows) {
                $sparklineData = array_map('intval', array_column($sparkRows, 'restraints'));
            }
        }

        View::render('data/browser', [
            'csv_api_type' => 'restraint-trends',
            'page_title'       => 'Restraint Data Browser',
            'page_stylesheet'  => 'data',
            'rows'             => $rows,
            'columns'          => ['District','School','Year','Enrollment','Students Restrained','Total Restraints','Injuries','Suppressed'],
            'column_keys'      => !empty($columnKeys) ? ['', '', '', 'enrollment','students_restrained','total_restraints','total_injuries',''] : [],
            'schoolYears'      => array_column($schoolYears, 'school_year'),
            'districts'        => $districts,
            'selectedYear'     => $schoolYear,
            'selectedDistrict' => $districtCode,
            'pagination'       => paginate($total, $perPage),
            'charts_html'      => $chartsHtml,
            'extra_html'       => $extraHtml,
            'state_averages'   => $stateAverages,
            'sparkline_data'   => $sparklineData,
        ]);
    }

    /** GET /data/prs */
    public function prs(array $params = []): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $total = (int)Database::fetchColumn("SELECT COUNT(*) FROM prs_intakes");
        $rows = Database::fetchAll(
            "SELECT p.prs_number, COALESCE(o.org_name, p.raw_agency_name) as agency,
                    p.intake_date, p.status, p.category, p.subcategory, p.closure_code
             FROM prs_intakes p
             LEFT JOIN organizations o ON p.org_id = o.id
             ORDER BY p.intake_date DESC
             LIMIT ? OFFSET ?",
            [$perPage, $offset]
        );

        // ── Charts ──
        $chartsHtml = '';
        $extraHtml = '';

        // 1. PRS category pie chart — server-side for correct label mapping
        $prsCatData = Database::fetchAllCached(
            "SELECT COALESCE(JSON_UNQUOTE(JSON_EXTRACT(allegations, '$.category')), 'Uncategorized') AS category,
                    COUNT(*) AS cnt
             FROM prs_cases GROUP BY category ORDER BY cnt DESC",
            [], 300
        );
        if ($prsCatData) {
            $catLabels = array_column($prsCatData, 'category');
            $catCounts = array_map('intval', array_column($prsCatData, 'cnt'));

            $pc = new Chart('prsCategoryPie', 'pie');
            $pc->setLabels($catLabels);
            $pc->addDataset('Cases', $catCounts);
            $chartsHtml .= '<div class="chart-card"><h3>PRS Cases by Category</h3>' . $pc->render() . '</div>';
        }

        // 2. PRS status funnel (horizontal bars)
        $statusData = Database::fetchAllCached(
            "SELECT status, COUNT(*) AS cnt FROM prs_intakes GROUP BY status ORDER BY cnt DESC",
            [], 300
        );
        if ($statusData) {
            $statusLabels = array_column($statusData, 'status');
            $statusCounts = array_map('intval', array_column($statusData, 'cnt'));
            $maxCount = max($statusCounts);

            $extraHtml .= '<div class="chart-card"><h3>PRS Case Status Distribution</h3><div class="status-bars">';
            $palette = ['#22c55e', '#60a5fa', '#f59e0b', '#ef4444', '#a78bfa', '#ec4899', '#ff5a1f', '#767676'];
            foreach ($statusData as $i => $row) {
                $pct = $maxCount > 0 ? round(($row['cnt'] / $maxCount) * 100) : 0;
                $color = $palette[$i % count($palette)];
                $label = h($row['status'] ?: 'Unknown');
                $extraHtml .= '<div class="status-bar-row">'
                    . '<span class="status-bar-label">' . $label . '</span>'
                    . '<div class="status-bar-track">'
                    . '<div class="status-bar-fill" style="width:' . $pct . '%;background:' . $color . '">'
                    . ($pct > 15 ? $label : '')
                    . '</div></div>'
                    . '<span class="status-bar-count">' . number_format($row['cnt']) . '</span>'
                    . '</div>';
            }
            $extraHtml .= '</div></div>';
        }

        View::render('data/browser', [
            'csv_api_type' => 'prs-categories',
            'page_title'      => 'PRS Intake Browser',
            'page_stylesheet' => 'data',
            'rows'            => $rows,
            'columns'         => ['PRS #','Agency','Intake Date','Status','Category','Subcategory','Closure'],
            'column_keys'     => [],
            'schoolYears'     => [],
            'districts'       => [],
            'pagination'      => paginate($total, $perPage),
            'charts_html'     => $chartsHtml,
            'extra_html'      => $extraHtml,
            'state_averages'  => [],
            'sparkline_data'  => null,
        ]);
    }

    /** GET /data/discipline */
    public function discipline(array $params = []): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $schoolYear = $_GET['school_year'] ?? null;
        $where = '';
        $bindings = [];
        if ($schoolYear) { $where = "WHERE d.school_year = ?"; $bindings[] = $schoolYear; }

        $total = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM discipline_data d $where", $bindings
        );
        $queryBindings = array_merge($bindings, [$perPage, $offset]);
        $rows = Database::fetchAll(
            "SELECT o.org_name, d.school_year, d.students, d.students_disciplined,
                    d.pct_in_school_susp, d.pct_out_school_susp
             FROM discipline_data d
             JOIN organizations o ON d.org_id = o.id
             $where
             ORDER BY o.org_name
             LIMIT ? OFFSET ?",
            $queryBindings
        );

        $schoolYears = Database::fetchAll("SELECT DISTINCT school_year FROM discipline_data ORDER BY school_year DESC");

        // ── Charts ──
        $chartsHtml = '';
        $extraHtml = '';

        // 1. Stacked bar chart from discipline-breakdown API data
        $breakdownData = Database::fetchAllCached(
            "SELECT school_year,
                    ROUND(SUM(students * pct_in_school_susp / 100))    AS in_school_susp,
                    ROUND(SUM(students * pct_out_school_susp / 100))   AS out_school_susp,
                    ROUND(SUM(students * pct_expulsion / 100))         AS expulsion,
                    ROUND(SUM(students * pct_alt_setting / 100))       AS alt_setting,
                    ROUND(SUM(students * pct_emergency_removal / 100)) AS emergency_removal,
                    ROUND(SUM(students * pct_arrest / 100))            AS arrest
             FROM discipline_data WHERE students > 0 GROUP BY school_year ORDER BY school_year",
            [], 300
        );
        if ($breakdownData) {
            $discYears = array_column($breakdownData, 'school_year');
            $dsc = new Chart('disciplineStackedChart', 'bar');
            $dsc->setLabels($discYears);
            $dsc->addDataset('In-School Susp', array_map('floatval', array_column($breakdownData, 'in_school_susp')), ['backgroundColor' => '#60a5fa']);
            $dsc->addDataset('Out-of-School Susp', array_map('floatval', array_column($breakdownData, 'out_school_susp')), ['backgroundColor' => '#f59e0b']);
            $dsc->addDataset('Expulsion', array_map('floatval', array_column($breakdownData, 'expulsion')), ['backgroundColor' => '#ef4444']);
            $dsc->addDataset('Alt Setting', array_map('floatval', array_column($breakdownData, 'alt_setting')), ['backgroundColor' => '#22c55e']);
            $dsc->addDataset('Emergency Removal', array_map('floatval', array_column($breakdownData, 'emergency_removal')), ['backgroundColor' => '#a78bfa']);
            $dsc->addDataset('Arrest', array_map('floatval', array_column($breakdownData, 'arrest')), ['backgroundColor' => '#ec4899']);
            $dsc->setOption('scales', [
                'x' => ['stacked' => true],
                'y' => ['stacked' => true, 'beginAtZero' => true, 'title' => ['display' => true, 'text' => 'Students', 'color' => '#a0a0a0']],
            ]);
            $dsc->setHeight(380);

            $chartsHtml .= '<div class="chart-card"><h3>Discipline Actions by Type</h3>' . $dsc->render() . '</div>';
        }

        // 2. Scatter: enrollment vs discipline rate (latest year)
        $latestYear = $schoolYear ?: (Database::fetchColumnCached("SELECT MAX(school_year) FROM discipline_data", [], 0, 300) ?: '2023-2024');
        $scatterData = Database::fetchAllCached(
            "SELECT o.org_name, d.students,
                    (d.pct_out_school_susp + d.pct_in_school_susp) AS discipline_rate
             FROM discipline_data d
             JOIN organizations o ON d.org_id = o.id
             WHERE d.school_year = ? AND d.students > 0
             ORDER BY d.students DESC",
            [$latestYear], 300
        );
        if ($scatterData) {
            $scatterPoints = [];
            foreach ($scatterData as $row) {
                $scatterPoints[] = ['x' => (int)$row['students'], 'y' => round((float)$row['discipline_rate'], 2)];
            }

            $scc = new Chart('disciplineScatterChart', 'scatter');
            $scc->addDataset('District', $scatterPoints, [
                'backgroundColor' => 'rgba(255,90,31,0.5)',
                'borderColor' => 'rgba(255,90,31,0.8)',
                'pointRadius' => 5,
                'pointHoverRadius' => 7,
            ]);
            $scc->setOption('scales', [
                'x' => ['title' => ['display' => true, 'text' => 'Enrollment', 'color' => '#a0a0a0']],
                'y' => ['title' => ['display' => true, 'text' => 'Discipline Rate %', 'color' => '#a0a0a0'], 'beginAtZero' => true],
            ]);
            $scc->setOption('plugins', ['legend' => ['display' => false]]);
            $scc->setHeight(380);

            $chartsHtml .= '<div class="chart-card"><h3>Enrollment vs Discipline Rate (' . h($latestYear) . ')</h3>' . $scc->render() . '</div>';
        }

        // 3. Doughnut: discipline type breakdown for latest year
        $latestBreakdown = Database::fetchAllCached(
            "SELECT SUM(pct_in_school_susp)    AS in_school,
                    SUM(pct_out_school_susp)   AS out_school,
                    SUM(pct_expulsion)         AS expulsion,
                    SUM(pct_alt_setting)       AS alt_setting,
                    SUM(pct_emergency_removal) AS emergency,
                    SUM(pct_arrest)            AS arrest,
                    SUM(students_disciplined)  AS total_disciplined
             FROM discipline_data WHERE school_year = ?",
            [$latestYear], 300
        );
        if ($latestBreakdown && $latestBreakdown[0]) {
            $lb = $latestBreakdown[0];
            $doughLabels = ['In-School Susp', 'Out-of-School Susp', 'Expulsion', 'Alt Setting', 'Emergency Removal', 'Arrest'];
            $doughVals = [
                round((float)$lb['in_school'], 1),
                round((float)$lb['out_school'], 1),
                round((float)$lb['expulsion'], 1),
                round((float)$lb['alt_setting'], 1),
                round((float)$lb['emergency'], 1),
                round((float)$lb['arrest'], 1),
            ];
            $totalDisc = (int)($lb['total_disciplined'] ?? 0);

            $dc = new Chart('disciplineDoughnutChart', 'doughnut');
            $dc->setLabels($doughLabels);
            $dc->addDataset('Discipline Type %', $doughVals);
            $dc->setOption('plugins', [
                'title' => ['display' => true, 'text' => number_format($totalDisc) . ' Students Disciplined', 'color' => '#a0a0a0', 'font' => ['family' => 'Inter', 'size' => 14]],
            ]);

            $extraHtml .= '<div class="chart-card"><h3>Discipline Type Breakdown (' . h($latestYear) . ')</h3>' . $dc->render() . '</div>';
        }

        // 4. Top 10 districts by discipline rate
        $top10 = Database::fetchAllCached(
            "SELECT o.org_name,
                    (d.pct_out_school_susp + d.pct_in_school_susp) AS discipline_rate
             FROM discipline_data d
             JOIN organizations o ON d.org_id = o.id
             WHERE d.school_year = ? AND d.students > 50
             ORDER BY discipline_rate DESC
             LIMIT 10",
            [$latestYear], 300
        );
        if ($top10) {
            $t10Labels = array_map(function($r) { return mb_strlen($r['org_name']) > 22 ? mb_substr($r['org_name'], 0, 21) . '…' : $r['org_name']; }, $top10);
            $t10Vals = array_map(function($r) { return round((float)$r['discipline_rate'], 2); }, $top10);

            $tc = new Chart('disciplineTop10Chart', 'bar');
            $tc->setLabels($t10Labels);
            $tc->addDataset('Discipline Rate %', $t10Vals, ['palette' => 'warm']);
            $tc->setOption('indexAxis', 'y');
            $tc->setOption('plugins', [
                'legend' => ['display' => false],
                'title' => ['display' => true, 'text' => 'Top 10 Districts by Discipline Rate', 'color' => '#a0a0a0', 'font' => ['family' => 'Inter', 'size' => 13]],
            ]);

            $extraHtml .= '<div class="chart-card"><h3>Top Districts by Discipline Rate (' . h($latestYear) . ')</h3>' . $tc->render() . '</div>';
        }


        // Data caveat: discipline values are cloned across years
        $extraHtml .= '<div style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.25);border-radius:6px;padding:0.75rem 1rem;margin-top:1rem;font-size:0.85rem;color:var(--text-secondary);"><strong style="color:var(--warning);">Note:</strong> Discipline metric values are from a single DESE annual snapshot and appear identical across all school years. Per-year breakdowns will become available when multi-year source data is imported. For year-over-year trend analysis, use the <a href="/data/restraint" style="color:var(--accent-glow);">Restraint dataset</a>.</div>';
        View::render('data/browser', [
            'csv_api_type' => 'discipline-breakdown',
            'page_title'      => 'Discipline Data Browser',
            'page_stylesheet' => 'data',
            'rows'            => $rows,
            'columns'         => ['District','Year','Students','Disciplined','In-School Susp %','Out-School Susp %'],
            'column_keys'     => [],
            'schoolYears'     => array_column($schoolYears, 'school_year'),
            'districts'       => [],
            'selectedYear'    => $schoolYear,
            'pagination'      => paginate($total, $perPage),
            'charts_html'     => $chartsHtml,
            'extra_html'      => $extraHtml,
            'state_averages'  => [],
            'sparkline_data'  => null,
        ]);
    }

    /** GET /data/enrollment */
    public function enrollment(array $params = []): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $districtCode = $_GET['district'] ?? null;

        $where = '';
        $bindings = [];
        if ($districtCode) { $where = "WHERE o.org_code = ?"; $bindings[] = $districtCode; }

        $total = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM enrollment_data e JOIN organizations o ON e.org_id = o.id $where", $bindings
        );

        $queryBindings = array_merge($bindings, [$perPage, $offset]);
        $rows = Database::fetchAll(
            "SELECT o.org_name, e.school_year, e.sped_pct, e.el_pct, e.low_income_pct, e.high_needs_pct
             FROM enrollment_data e
             JOIN organizations o ON e.org_id = o.id
             $where
             ORDER BY o.org_name
             LIMIT ? OFFSET ?",
            $queryBindings
        );

        // ── Charts ──
        $chartsHtml = '';
        $extraHtml = '';

        // 1. Demographics doughnut (latest year)
        $latestEnrollYear = Database::fetchColumnCached("SELECT MAX(school_year) FROM enrollment_data", [], 0, 300);
        $latestEnroll = Database::fetchAllCached(
            "SELECT AVG(sped_pct) AS sped, AVG(el_pct) AS el,
                    AVG(low_income_pct) AS low_income, AVG(high_needs_pct) AS high_needs
             FROM enrollment_data WHERE school_year = ?",
            [$latestEnrollYear], 300
        );
        if ($latestEnroll && $latestEnroll[0]) {
            $le = $latestEnroll[0];
            $dLabels = ['SPED', 'English Learner', 'Low Income', 'High Needs'];
            $dVals = [
                round((float)$le['sped'], 1),
                round((float)$le['el'], 1),
                round((float)$le['low_income'], 1),
                round((float)$le['high_needs'], 1),
            ];

            $edc = new Chart('enrollmentDoughnutChart', 'doughnut');
            $edc->setLabels($dLabels);
            $edc->addDataset('% of Enrollment', $dVals);
            $chartsHtml .= '<div class="charts-grid"><div class="chart-card"><h3>Statewide Demographics (' . h($latestEnrollYear) . ')</h3>' . $edc->render() . '</div></div>';
        }

        // 2. Trend lines (async from API) — always show
        $etc = new Chart('enrollmentTrendChart', 'line');
        $chartsHtml .= '<div class="chart-card"><h3>Demographic Trends Over Time</h3>' . $etc->renderAsync('/api/data?type=enrollment-demographics') . '</div>';

        // 3. District profile card
        if ($districtCode) {
            $distProf = Database::fetchAllCached(
                "SELECT e.school_year, e.sped_pct, e.el_pct, e.low_income_pct, e.high_needs_pct
                 FROM enrollment_data e JOIN organizations o ON e.org_id = o.id
                 WHERE o.org_code = ?
                 ORDER BY e.school_year DESC LIMIT 1",
                [$districtCode], 300
            );
            if ($distProf && $distProf[0]) {
                $dp = $distProf[0];
                // State averages for comparison
                $stateAvgs = Database::fetchAllCached(
                    "SELECT AVG(sped_pct) AS sped, AVG(el_pct) AS el,
                            AVG(low_income_pct) AS low_income, AVG(high_needs_pct) AS high_needs
                     FROM enrollment_data WHERE school_year = ?",
                    [$dp['school_year']], 300
                );
                $sa = $stateAvgs[0] ?? null;

                $gauges = [
                    ['label' => 'SPED %', 'val' => (float)$dp['sped_pct'], 'state' => $sa ? (float)$sa['sped'] : 0],
                    ['label' => 'English Learner %', 'val' => (float)$dp['el_pct'], 'state' => $sa ? (float)$sa['el'] : 0],
                    ['label' => 'Low Income %', 'val' => (float)$dp['low_income_pct'], 'state' => $sa ? (float)$sa['low_income'] : 0],
                    ['label' => 'High Needs %', 'val' => (float)$dp['high_needs_pct'], 'state' => $sa ? (float)$sa['high_needs'] : 0],
                ];

                $extraHtml .= '<div class="chart-card"><h3>District Profile — ' . h($dp['school_year']) . '</h3><div class="profile-card">';
                foreach ($gauges as $g) {
                    $maxScale = max($g['val'], $g['state']) * 1.3;
                    if ($maxScale < 1) $maxScale = 100;
                    $valPct = ($g['val'] / $maxScale) * 100;
                    $statePct = ($g['state'] / $maxScale) * 100;
                    $extraHtml .= '<div class="profile-gauge">'
                        . '<span class="profile-gauge-label"><span>' . h($g['label']) . '</span><span>' . number_format($g['val'], 1) . '%</span></span>'
                        . '<div class="profile-gauge-bar" style="position:relative">'
                        . '<div class="profile-gauge-fill" style="width:' . $valPct . '%"></div>'
                        . '<div class="profile-gauge-marker" style="left:' . $statePct . '%" title="State avg: ' . number_format($g['state'], 1) . '%"></div>'
                        . '</div></div>';
                }
                $extraHtml .= '</div></div>';
            }
        }

        // 4. High-needs concentration (top 15)
        $hnData = Database::fetchAllCached(
            "SELECT o.org_name, AVG(e.high_needs_pct) AS high_needs
             FROM enrollment_data e
             JOIN organizations o ON e.org_id = o.id
             GROUP BY o.org_name
             ORDER BY high_needs DESC
             LIMIT 15",
            [], 300
        );
        if ($hnData) {
            $hnLabels = array_map(function($r) { return mb_strlen($r['org_name']) > 22 ? mb_substr($r['org_name'], 0, 21) . '…' : $r['org_name']; }, $hnData);
            $hnVals = array_map(function($r) { return round((float)$r['high_needs'], 1); }, $hnData);

            $hnc = new Chart('highNeedsChart', 'bar');
            $hnc->setLabels($hnLabels);
            $hnc->addDataset('High Needs %', $hnVals, ['palette' => 'warm']);
            $hnc->setOption('indexAxis', 'y');
            $hnc->setOption('plugins', [
                'legend' => ['display' => false],
                'title' => ['display' => true, 'text' => 'Top 15 Districts by High-Needs Concentration', 'color' => '#a0a0a0', 'font' => ['family' => 'Inter', 'size' => 13]],
            ]);

            $extraHtml .= '<div class="chart-card"><h3>High-Needs Concentration</h3>' . $hnc->render() . '</div>';
        }

        View::render('data/browser', [
            'csv_api_type' => 'enrollment-demographics',
            'page_title'      => 'Enrollment Data Browser',
            'page_stylesheet' => 'data',
            'rows'            => $rows,
            'columns'         => ['District','Year','SPED %','EL %','Low Income %','High Needs %'],
            'column_keys'     => [],
            'schoolYears'     => [],
            'districts'       => [],
            'selectedDistrict'=> $districtCode,
            'pagination'      => paginate($total, $perPage),
            'charts_html'     => $chartsHtml,
            'extra_html'      => $extraHtml,
            'state_averages'  => [],
            'sparkline_data'  => null,
        ]);
    }

    /** GET /data/attendance */
    public function attendance(array $params = []): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $total = (int)Database::fetchColumn("SELECT COUNT(*) FROM attendance_data");
        $rows = Database::fetchAll(
            "SELECT o.org_name, a.school_year, a.attendance_rate, a.chronically_absent_10_pct
             FROM attendance_data a
             JOIN organizations o ON a.org_id = o.id
             ORDER BY o.org_name
             LIMIT ? OFFSET ?",
            [$perPage, $offset]
        );

        // ── Charts ──
        $chartsHtml = '';
        $extraHtml = '';

        // 1. Attendance trend line with 95% target + chronic absenteeism dual-axis
        $attTrend = Database::fetchAllCached(
            "SELECT school_year,
                    AVG(attendance_rate)           AS att_rate,
                    AVG(chronically_absent_10_pct) AS chronic
             FROM attendance_data GROUP BY school_year ORDER BY school_year",
            [], 300
        );
        if ($attTrend) {
            $attLabels = array_column($attTrend, 'school_year');
            $attRates = array_map(function($r) { return round((float)$r, 1); }, array_column($attTrend, 'att_rate'));
            $chronicRates = array_map(function($r) { return round((float)$r, 1); }, array_column($attTrend, 'chronic'));

            $atc = new Chart('attendanceTrendChart', 'line');
            $atc->setLabels($attLabels);
            $atc->addDataset('Attendance Rate %', $attRates, [
                'borderColor' => '#22c55e',
                'backgroundColor' => 'rgba(34,197,94,0.1)',
                'borderWidth' => 2,
                'pointRadius' => 4,
                'pointBackgroundColor' => '#22c55e',
                'tension' => 0.3,
                'fill' => true,
            ]);
            $atc->addDataset('Chronic Absenteeism %', $chronicRates, [
                'borderColor' => '#ef4444',
                'backgroundColor' => 'rgba(239,68,68,0.1)',
                'borderWidth' => 2,
                'pointRadius' => 4,
                'pointBackgroundColor' => '#ef4444',
                'tension' => 0.3,
                'fill' => true,
                'yAxisID' => 'y1',
            ]);
            // 95% target line as a third dataset
            $target95 = array_fill(0, count($attLabels), 95);
            $atc->addDataset('95% Target', $target95, [
                'borderColor' => 'rgba(245,158,11,0.6)',
                'borderDash' => [8, 4],
                'borderWidth' => 2,
                'pointRadius' => 0,
                'fill' => false,
                'backgroundColor' => 'transparent',
            ]);
            $atc->setOption('scales', [
                'y' => ['min' => 85, 'max' => 100, 'ticks' => ['callback' => 'function(v){return v+"%"}'], 'title' => ['display' => true, 'text' => 'Attendance Rate', 'color' => '#a0a0a0']],
                'y1' => ['position' => 'right', 'ticks' => ['callback' => 'function(v){return v+"%"}'], 'grid' => ['display' => false], 'title' => ['display' => true, 'text' => 'Chronic Absenteeism', 'color' => '#a0a0a0']],
            ]);
            $atc->setHeight(380);

            $chartsHtml .= '<div class="chart-card"><h3>Attendance Trends with 95% Target</h3>' . $atc->render() . '</div>';
        }

        // 2. Bottom 15 districts by attendance
        $latestAttYear = Database::fetchColumn("SELECT MAX(school_year) FROM attendance_data");
        $bottom15 = Database::fetchAllCached(
            "SELECT o.org_name, a.attendance_rate
             FROM attendance_data a
             JOIN organizations o ON a.org_id = o.id
             WHERE a.school_year = ? AND a.attendance_rate > 0
             ORDER BY a.attendance_rate ASC
             LIMIT 15",
            [$latestAttYear], 300
        );
        if ($bottom15) {
            $bLabels = array_map(function($r) { return mb_strlen($r['org_name']) > 22 ? mb_substr($r['org_name'], 0, 21) . '…' : $r['org_name']; }, $bottom15);
            $bVals = array_map(function($r) { return round((float)$r['attendance_rate'], 1); }, $bottom15);

            $bac = new Chart('attendanceBottom15Chart', 'bar');
            $bac->setLabels($bLabels);
            $bac->addDataset('Attendance Rate %', $bVals, ['palette' => 'sentiment']);
            $bac->setOption('indexAxis', 'y');
            $bac->setOption('scales', ['x' => ['min' => 80, 'max' => 100]]);
            $bac->setOption('plugins', [
                'legend' => ['display' => false],
                'title' => ['display' => true, 'text' => 'Bottom 15 Districts by Attendance (' . h($latestAttYear) . ')', 'color' => '#a0a0a0', 'font' => ['family' => 'Inter', 'size' => 13]],
            ]);

            // 3. YoY attendance change
            $yoyData = Database::fetchAllCached(
                "SELECT school_year, AVG(attendance_rate) AS att_rate
                 FROM attendance_data GROUP BY school_year ORDER BY school_year",
                [], 300
            );
            if (count($yoyData) >= 2) {
                $yoyLabels = [];
                $yoyChanges = [];
                $yoyColors = [];
                for ($i = 1; $i < count($yoyData); $i++) {
                    $yoyLabels[] = $yoyData[$i]['school_year'];
                    $change = round((float)$yoyData[$i]['att_rate'] - (float)$yoyData[$i-1]['att_rate'], 2);
                    $yoyChanges[] = $change;
                    $yoyColors[] = $change >= 0 ? 'rgba(34,197,94,0.7)' : 'rgba(239,68,68,0.7)';
                }

                $yoyc = new Chart('attendanceYoYChart', 'bar');
                $yoyc->setLabels($yoyLabels);
                $yoyc->addDataset('Year-over-Year Change (pp)', $yoyChanges, [
                    'backgroundColor' => $yoyColors,
                    'borderColor' => $yoyColors,
                    'borderRadius' => 4,
                ]);
                $yoyc->setOption('plugins', [
                    'legend' => ['display' => false],
                    'title' => ['display' => true, 'text' => 'Year-over-Year Attendance Change', 'color' => '#a0a0a0', 'font' => ['family' => 'Inter', 'size' => 13]],
                ]);

                $extraHtml .= '<div class="charts-grid"><div class="chart-card"><h3>Attendance Ranking</h3>' . $bac->render() . '</div>'
                    . '<div class="chart-card"><h3>YoY Change</h3>' . $yoyc->render() . '</div></div>';
            } else {
                $extraHtml .= '<div class="chart-card"><h3>Attendance Ranking</h3>' . $bac->render() . '</div>';
            }
        }

        View::render('data/browser', [
            'csv_api_type' => 'attendance-trends',
            'page_title'      => 'Attendance Data Browser',
            'page_stylesheet' => 'data',
            'rows'            => $rows,
            'columns'         => ['District','Year','Attendance Rate','Chronic Absenteeism %'],
            'column_keys'     => [],
            'schoolYears'     => [],
            'districts'       => [],
            'pagination'      => paginate($total, $perPage),
            'charts_html'     => $chartsHtml,
            'extra_html'      => $extraHtml,
            'state_averages'  => [],
            'sparkline_data'  => null,
        ]);
    }

    /** GET /data/sped-results */
    public function spedResults(array $params = []): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $total = (int)Database::fetchColumn("SELECT COUNT(*) FROM sped_results");
        $rows = Database::fetchAll(
            "SELECT o.org_name, s.school_year, s.sped_grad_rate, s.sped_dropout_rate, s.lre_full_incl_pct
             FROM sped_results s
             JOIN organizations o ON s.org_id = o.id
             ORDER BY o.org_name
             LIMIT ? OFFSET ?",
            [$perPage, $offset]
        );

        // ── Charts ──
        $chartsHtml = '';
        $extraHtml = '';

        // 1. Grouped bar chart (3 metrics per year)
        $spedTrend = Database::fetchAllCached(
            "SELECT school_year,
                    AVG(sped_grad_rate)     AS grad_rate,
                    AVG(sped_dropout_rate)  AS dropout_rate,
                    AVG(lre_full_incl_pct)  AS inclusion_pct
             FROM sped_results GROUP BY school_year ORDER BY school_year",
            [], 300
        );
        if ($spedTrend) {
            $spLabels = array_column($spedTrend, 'school_year');
            $spGrad = array_map(function($r) { return round((float)$r, 1); }, array_column($spedTrend, 'grad_rate'));
            $spDrop = array_map(function($r) { return round((float)$r, 1); }, array_column($spedTrend, 'dropout_rate'));
            $spIncl = array_map(function($r) { return round((float)$r, 1); }, array_column($spedTrend, 'inclusion_pct'));

            $spc = new Chart('spedGroupedChart', 'bar');
            $spc->setLabels($spLabels);
            $spc->addDataset('Grad Rate %', $spGrad, [
                'backgroundColor' => 'rgba(34,197,94,0.6)',
                'borderColor' => 'rgba(34,197,94,0.9)',
                'borderWidth' => 1,
                'borderRadius' => 4,
            ]);
            $spc->addDataset('Dropout Rate %', $spDrop, [
                'backgroundColor' => 'rgba(239,68,68,0.6)',
                'borderColor' => 'rgba(239,68,68,0.9)',
                'borderWidth' => 1,
                'borderRadius' => 4,
            ]);
            $spc->addDataset('Inclusion %', $spIncl, [
                'backgroundColor' => 'rgba(96,165,250,0.6)',
                'borderColor' => 'rgba(96,165,250,0.9)',
                'borderWidth' => 1,
                'borderRadius' => 4,
            ]);
            $spc->setHeight(380);

            $chartsHtml .= '<div class="chart-card"><h3>SPED Outcomes Over Time</h3>' . $spc->render() . '</div>';
        }

        // 2. SPED vs General Ed gap — diverging horizontal bars
        // Approximate gap: compare each district's sped_grad_rate to the statewide gen_ed grad rate
        $latestSpedYear = Database::fetchColumn("SELECT MAX(school_year) FROM sped_results");
        $gapData = Database::fetchAllCached(
            "SELECT o.org_name, s.sped_grad_rate
             FROM sped_results s
             JOIN organizations o ON s.org_id = o.id
             WHERE s.school_year = ? AND s.sped_grad_rate > 0
             ORDER BY s.sped_grad_rate DESC
             LIMIT 15",
            [$latestSpedYear], 300
        );
        if ($gapData) {
            // State average SPED grad rate as reference
            $stateSpedGrad = Database::fetchColumnCached(
                "SELECT AVG(sped_grad_rate) FROM sped_results WHERE school_year = ?",
                [$latestSpedYear], 0, 300
            );
            $refRate = (float)($stateSpedGrad ?? 85);

            $extraHtml .= '<div class="chart-card"><h3>SPED Graduation Rate vs State Avg (' . h($latestSpedYear) . ')</h3>'
                . '<div class="diverging-container">';
            foreach ($gapData as $row) {
                $val = (float)$row['grad_rate'];
                $gap = round($val - $refRate, 1);
                $absGap = abs($gap);
                $maxGap = 15;
                $barPct = min(100, ($absGap / $maxGap) * 100);
                $dirClass = $gap >= 0 ? 'positive' : 'negative';
                $sign = $gap >= 0 ? '+' : '';
                $extraHtml .= '<div class="diverging-row">'
                    . '<span class="diverging-label">' . h(mb_strlen($row['org_name']) > 20 ? mb_substr($row['org_name'], 0, 19) . '…' : $row['org_name']) . '</span>'
                    . '<div class="diverging-bar-wrap">'
                    . '<div class="diverging-bar-center"></div>'
                    . '<div class="diverging-bar-fill ' . $dirClass . '" style="width:' . $barPct . '%"></div>'
                    . '</div>'
                    . '<span class="diverging-value" style="color:' . ($gap >= 0 ? 'var(--success)' : 'var(--danger)') . '">' . $sign . number_format($gap, 1) . 'pp</span>'
                    . '</div>';
            }
            $extraHtml .= '<div style="text-align:center;font-size:0.8rem;color:var(--text-muted);margin-top:0.5rem">'
                . 'State average SPED grad rate: ' . number_format($refRate, 1) . '%</div></div></div>';
        }

        View::render('data/browser', [
            'csv_api_type' => 'sped-outcomes',
            'page_title'      => 'SPED Results Browser',
            'page_stylesheet' => 'data',
            'rows'            => $rows,
            'columns'         => ['District','Year','Grad Rate %','Dropout Rate %','Full Inclusion %'],
            'column_keys'     => [],
            'schoolYears'     => [],
            'districts'       => [],
            'pagination'      => paginate($total, $perPage),
            'charts_html'     => $chartsHtml,
            'extra_html'      => $extraHtml,
            'state_averages'  => [],
            'sparkline_data'  => null,
        ]);
    }
}
