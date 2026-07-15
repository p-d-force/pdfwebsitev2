<?php declare(strict_types=1);
namespace App\Controllers;

use App\Core\View;
use App\Core\Database;

class DataPortalController
{
    public function index(array $params = []): void
    {
        View::render('data-portal', [
            'page_title'      => 'Data Portal',
            'page_stylesheet' => 'data',
        ]);
    }

    /** GET /compare — district comparison */
    public function compare(array $params = []): void
    {
        $allDistricts = Database::fetchAllCached(
            "SELECT org_code, org_name FROM organizations
             WHERE org_type = 'Public School District' AND is_active = 1
             ORDER BY org_name",
            [], 60
        );

        $years = Database::fetchAllCached(
            "SELECT DISTINCT school_year FROM restraint_data ORDER BY school_year DESC",
            [], 300
        );
        $years = array_column($years, 'school_year');

        $selectedCodes = $_GET['districts'] ?? [];
        if (!is_array($selectedCodes)) $selectedCodes = [$selectedCodes];
        $selectedYear = $_GET['school_year'] ?? ($years[0] ?? '');

        $comparisonData = [];
        if (!empty($selectedCodes) && !empty($selectedYear)) {
            $placeholders = implode(',', array_fill(0, count($selectedCodes), '?'));
            $bindings = array_merge([$selectedYear], $selectedCodes);
            $comparisonData = Database::fetchAll(
                "SELECT d.org_name, SUM(r.enrollment) as enrollment,
                        SUM(r.students_restrained) as students_restrained,
                        SUM(r.total_restraints) as total_restraints,
                        SUM(r.total_injuries) as total_injuries
                 FROM restraint_data r
                 JOIN organizations s ON r.org_id = s.id
                 JOIN organizations d ON s.parent_org_id = d.id
                 WHERE r.school_year = ? AND d.org_code IN ($placeholders)
                 GROUP BY d.id, d.org_name
                 ORDER BY d.org_name",
                $bindings
            );
        }

        View::render('compare', [
            'page_title'      => 'District Comparison',
            'page_stylesheet' => 'data',
            'allDistricts'    => $allDistricts,
            'years'           => $years,
            'selectedCodes'   => $selectedCodes,
            'selectedYear'    => $selectedYear,
            'comparisonData'  => $comparisonData,
        ]);
    }

    /** GET /data/dashboard — statewide data dashboard */
    public function dashboard(array $params = []): void
    {
        // ── Determine school years ──
        $years = Database::fetchAll(
            "SELECT DISTINCT school_year FROM restraint_data ORDER BY school_year DESC"
        );
        $latestYear = $years[0]['school_year'] ?? '';
        $prevYear   = $years[1]['school_year'] ?? '';

        // ── 1. Stat cards ──
        // Current-year statewide totals
        $curStats = Database::fetch(
            "SELECT
                (SELECT SUM(enrollment) FROM restraint_data WHERE school_year = ?) as total_students,
                (SELECT SUM(total_restraints) FROM restraint_data WHERE school_year = ?) as total_restraints,
                (SELECT ROUND(AVG(attendance_rate), 1) FROM attendance_data WHERE school_year = ?) as avg_attendance,
                (SELECT ROUND(AVG(sped_grad_rate), 1) FROM sped_results WHERE school_year = ?) as avg_sped_grad,
                (SELECT COUNT(*) FROM prs_cases) as total_prs,
                (SELECT COUNT(DISTINCT d.id)
                 FROM restraint_data r
                 JOIN organizations s ON r.org_id = s.id
                 JOIN organizations d ON s.parent_org_id = d.id
                 WHERE r.school_year = ? AND d.org_type = 'Public School District') as districts_reporting",
            [$latestYear, $latestYear, $latestYear, $latestYear, $latestYear]
        );

        // Previous-year totals for YoY deltas
        $prevStats = Database::fetch(
            "SELECT
                (SELECT SUM(enrollment) FROM restraint_data WHERE school_year = ?) as total_students,
                (SELECT SUM(total_restraints) FROM restraint_data WHERE school_year = ?) as total_restraints,
                (SELECT ROUND(AVG(attendance_rate), 1) FROM attendance_data WHERE school_year = ?) as avg_attendance,
                (SELECT ROUND(AVG(sped_grad_rate), 1) FROM sped_results WHERE school_year = ?) as avg_sped_grad",
            [$prevYear, $prevYear, $prevYear, $prevYear]
        );

        // ── 2. 2×2 Chart Grid ──

        // Restraint trends (bar + line)
        $restraintTrends = Database::fetchAll(
            "SELECT school_year, SUM(total_restraints) as restraints,
                    SUM(enrollment) as enrollment
             FROM restraint_data
             GROUP BY school_year ORDER BY school_year"
        );

        // Discipline breakdown
        $disciplineData = Database::fetchAll(
            "SELECT school_year,
                    ROUND(AVG(pct_in_school_susp), 1) as in_school,
                    ROUND(AVG(pct_out_school_susp), 1) as out_school,
                    ROUND(AVG(pct_expulsion), 1) as expulsion
             FROM discipline_data
             GROUP BY school_year ORDER BY school_year"
        );

        // Enrollment demographics for latest year
        $demos = Database::fetch(
            "SELECT COALESCE(SUM(sped_num),0) as sped,
                    COALESCE(SUM(el_num),0) as el,
                    COALESCE(SUM(low_income_num),0) as low_income,
                    COALESCE(SUM(high_needs_num),0) as high_needs
             FROM enrollment_data WHERE school_year = ?",
            [$latestYear]
        );

        // Attendance trends
        $attendanceTrends = Database::fetchAll(
            "SELECT school_year, ROUND(AVG(attendance_rate), 1) as attendance
             FROM attendance_data GROUP BY school_year ORDER BY school_year"
        );

        // SPED outcomes trends
        $spedTrends = Database::fetchAll(
            "SELECT school_year, ROUND(AVG(sped_grad_rate), 1) as sped_grad
             FROM sped_results GROUP BY school_year ORDER BY school_year"
        );

        // ── 3. Correlation data ──
        // Aggregate restraint_data to district level for latest year
        $correlation = Database::fetchAll(
            "SELECT d.org_name, d.org_code,
                    SUM(r.total_restraints) as total_restraints,
                    SUM(r.enrollment) as enrollment,
                    disc.pct_in_school_susp as in_school_susp,
                    disc.pct_out_school_susp as out_school_susp,
                    disc.pct_expulsion as expulsion,
                    enroll.low_income_pct,
                    sped.sped_grad_rate,
                    att.attendance_rate
             FROM organizations d
             JOIN organizations s ON s.parent_org_id = d.id
             JOIN restraint_data r ON r.org_id = s.id AND r.school_year = ?
             LEFT JOIN discipline_data disc ON disc.org_id = d.id AND disc.school_year = ?
             LEFT JOIN enrollment_data enroll ON enroll.org_id = d.id AND enroll.school_year = ?
             LEFT JOIN sped_results sped ON sped.org_id = d.id AND sped.school_year = ?
             LEFT JOIN attendance_data att ON att.org_id = d.id AND att.school_year = ?
             WHERE d.org_type = 'Public School District' AND d.is_active = 1
             GROUP BY d.id, d.org_name, d.org_code,
                      disc.pct_in_school_susp, disc.pct_out_school_susp, disc.pct_expulsion,
                      enroll.low_income_pct, sped.sped_grad_rate, att.attendance_rate",
            [$latestYear, $latestYear, $latestYear, $latestYear, $latestYear]
        );

        // ── 4. YoY tracking ──
        $yoyData = Database::fetchAll(
            "SELECT d.org_name, d.org_code,
                    cur_r.total_restraints as cur_restraints,
                    COALESCE(prev_r.total_restraints, 0) as prev_restraints,
                    cur_a.attendance_rate as cur_attendance,
                    COALESCE(prev_a.attendance_rate, 0) as prev_attendance
             FROM organizations d
             LEFT JOIN (
                 SELECT s.parent_org_id as did,
                        SUM(r.total_restraints) as total_restraints
                 FROM restraint_data r
                 JOIN organizations s ON r.org_id = s.id
                 WHERE r.school_year = ?
                 GROUP BY s.parent_org_id
             ) cur_r ON cur_r.did = d.id
             LEFT JOIN (
                 SELECT s.parent_org_id as did,
                        SUM(r.total_restraints) as total_restraints
                 FROM restraint_data r
                 JOIN organizations s ON r.org_id = s.id
                 WHERE r.school_year = ?
                 GROUP BY s.parent_org_id
             ) prev_r ON prev_r.did = d.id
             LEFT JOIN attendance_data cur_a ON cur_a.org_id = d.id AND cur_a.school_year = ?
             LEFT JOIN attendance_data prev_a ON prev_a.org_id = d.id AND prev_a.school_year = ?
             WHERE d.org_type = 'Public School District' AND d.is_active = 1
             ORDER BY (cur_r.total_restraints - COALESCE(prev_r.total_restraints, 0)) DESC",
            [$latestYear, $prevYear, $latestYear, $prevYear]
        );
        // Biggest movers — $yoyData is sorted by (cur - prev) DESC (largest increase first)
        $declinedAll = array_values(array_filter($yoyData, fn($r) => ($r['cur_restraints'] ?? 0) > ($r['prev_restraints'] ?? 0)));
        $improvedAll = array_values(array_filter($yoyData, fn($r) => ($r['cur_restraints'] ?? 0) < ($r['prev_restraints'] ?? 0)));
        $mostDeclined = array_slice($declinedAll, 0, 5);
        $mostImproved = array_slice(array_reverse($improvedAll), 0, 5);

        // ── 5. Rankings ──
        // Highest Restraint (aggregated to district)
        $rankRestraintTop = Database::fetchAll(
            "SELECT d.org_name, d.org_code,
                    SUM(r.total_restraints) as total,
                    SUM(r.enrollment) as enrollment
             FROM organizations d
             JOIN organizations s ON s.parent_org_id = d.id
             JOIN restraint_data r ON r.org_id = s.id AND r.school_year = ?
             WHERE d.org_type = 'Public School District'
             GROUP BY d.id, d.org_name, d.org_code
             ORDER BY total DESC LIMIT 10",
            [$latestYear]
        );
        $rankRestraintBottom = Database::fetchAll(
            "SELECT d.org_name, d.org_code,
                    SUM(r.total_restraints) as total,
                    SUM(r.enrollment) as enrollment
             FROM organizations d
             JOIN organizations s ON s.parent_org_id = d.id
             JOIN restraint_data r ON r.org_id = s.id AND r.school_year = ?
             WHERE d.org_type = 'Public School District'
             GROUP BY d.id, d.org_name, d.org_code
             HAVING total > 0
             ORDER BY total ASC LIMIT 10",
            [$latestYear]
        );

        // Lowest Attendance
        $rankAttendanceBottom = Database::fetchAll(
            "SELECT d.org_name, d.org_code, a.attendance_rate as val
             FROM organizations d
             JOIN attendance_data a ON a.org_id = d.id AND a.school_year = ?
             WHERE d.org_type = 'Public School District' AND a.attendance_rate IS NOT NULL
             ORDER BY a.attendance_rate ASC LIMIT 10",
            [$latestYear]
        );
        $rankAttendanceTop = Database::fetchAll(
            "SELECT d.org_name, d.org_code, a.attendance_rate as val
             FROM organizations d
             JOIN attendance_data a ON a.org_id = d.id AND a.school_year = ?
             WHERE d.org_type = 'Public School District' AND a.attendance_rate IS NOT NULL
             ORDER BY a.attendance_rate DESC LIMIT 10",
            [$latestYear]
        );

        // Highest SPED Gap (lowest SPED grad rate)
        $rankSpedBottom = Database::fetchAll(
            "SELECT d.org_name, d.org_code, s.sped_grad_rate as val
             FROM organizations d
             JOIN sped_results s ON s.org_id = d.id AND s.school_year = ?
             WHERE d.org_type = 'Public School District' AND s.sped_grad_rate IS NOT NULL
             ORDER BY s.sped_grad_rate ASC LIMIT 10",
            [$latestYear]
        );
        $rankSpedTop = Database::fetchAll(
            "SELECT d.org_name, d.org_code, s.sped_grad_rate as val
             FROM organizations d
             JOIN sped_results s ON s.org_id = d.id AND s.school_year = ?
             WHERE d.org_type = 'Public School District' AND s.sped_grad_rate IS NOT NULL
             ORDER BY s.sped_grad_rate DESC LIMIT 10",
            [$latestYear]
        );

        // Most PRS
        $rankPrs = Database::fetchAll(
            "SELECT o.org_name, o.org_code, COUNT(p.id) as val
             FROM organizations o
             LEFT JOIN prs_cases p ON p.org_id = o.id
             WHERE o.org_type = 'Public School District' AND o.is_active = 1
             GROUP BY o.id, o.org_name, o.org_code
             ORDER BY val DESC LIMIT 10",
            []
        );

        View::render('data-dashboard', [
            'page_title'      => 'Statewide Dashboard',
            'page_stylesheet' => 'data',
            'latestYear'      => $latestYear,
            'prevYear'        => $prevYear,
            'curStats'        => $curStats,
            'prevStats'       => $prevStats,
            'restraintTrends' => $restraintTrends,
            'disciplineData'  => $disciplineData,
            'demos'           => $demos,
            'attendanceTrends'=> $attendanceTrends,
            'spedTrends'      => $spedTrends,
            'correlation'     => $correlation,
            'yoyData'         => $yoyData,
            'mostImproved'    => $mostImproved,
            'mostDeclined'    => $mostDeclined,
            'rankRestraintTop'    => $rankRestraintTop,
            'rankRestraintBottom' => $rankRestraintBottom,
            'rankAttendanceTop'   => $rankAttendanceTop,
            'rankAttendanceBottom'=> $rankAttendanceBottom,
            'rankSpedTop'         => $rankSpedTop,
            'rankSpedBottom'      => $rankSpedBottom,
            'rankPrs'             => $rankPrs,
        ]);
    }

    /** GET /data/combined — side-by-side PRS + DESE data for a district */
    public function combined(array $params = []): void
    {
        $districtCode = $_GET['district'] ?? '';

        $districts = Database::fetchAllCached(
            "SELECT o.org_code, o.org_name
             FROM organizations o
             WHERE o.org_type = 'Public School District' AND o.is_active = 1
             ORDER BY o.org_name",
            [], 300
        );

        $selectedDistrict = null;
        $prsCases = [];
        $prsTotal = 0;
        $deseRestraint = [];
        $deseDiscipline = [];
        $deseAttendance = [];
        $deseEnrollment = null;
        $deseSped = null;
        $chartHtml = '';
        $mergedByYear = [];

        if (!empty($districtCode)) {
            $selectedDistrict = Database::fetch(
                "SELECT id, org_code, org_name FROM organizations WHERE org_code = ?", [$districtCode]
            );

            if ($selectedDistrict) {
                $did = (int)$selectedDistrict['id'];

                // PRS data
                $prsTotal = (int)Database::fetchColumn("SELECT COUNT(*) FROM prs_cases WHERE org_id = ?", [$did]);
                $prsCases = Database::fetchAll(
                    "SELECT prs_number, case_title, current_status, filing_date, resolution_type
                     FROM prs_cases WHERE org_id = ?
                     ORDER BY filing_date DESC LIMIT 20",
                    [$did]
                );

                $prsByYear = Database::fetchAll(
                    "SELECT YEAR(filing_date) as yr, COUNT(*) as cnt
                     FROM prs_cases WHERE org_id = ? AND filing_date IS NOT NULL
                     GROUP BY yr ORDER BY yr",
                    [$did]
                );

                // DESE restraint data
                $deseRestraint = Database::fetchAll(
                    "SELECT r.school_year, SUM(r.total_restraints) as restraints, SUM(r.enrollment) as enrollment
                     FROM restraint_data r JOIN organizations s ON r.org_id = s.id
                     WHERE s.parent_org_id = ? GROUP BY r.school_year ORDER BY r.school_year DESC LIMIT 5",
                    [$did]
                );
                if (empty($deseRestraint)) {
                    $deseRestraint = Database::fetchAll(
                        "SELECT school_year, total_restraints as restraints, enrollment
                         FROM restraint_data WHERE org_id = ? ORDER BY school_year DESC LIMIT 5",
                        [$did]
                    );
                }

                // Discipline
                $deseDiscipline = Database::fetchAll(
                    "SELECT school_year, students, students_disciplined,
                            pct_in_school_susp, pct_out_school_susp, pct_expulsion
                     FROM discipline_data WHERE org_id = ? ORDER BY school_year DESC LIMIT 5",
                    [$did]
                );

                // Attendance
                $deseAttendance = Database::fetchAll(
                    "SELECT school_year, attendance_rate, chronically_absent_10_pct
                     FROM attendance_data WHERE org_id = ? ORDER BY school_year DESC LIMIT 5",
                    [$did]
                );

                // Enrollment demographics
                $deseEnrollment = Database::fetch(
                    "SELECT school_year, sped_pct, el_pct, low_income_pct, high_needs_pct
                     FROM enrollment_data WHERE org_id = ? ORDER BY school_year DESC LIMIT 1",
                    [$did]
                );

                // SPED outcomes
                $deseSped = Database::fetch(
                    "SELECT school_year, sped_grad_rate, sped_dropout_rate, lre_full_incl_pct
                     FROM sped_results WHERE org_id = ? ORDER BY school_year DESC LIMIT 1",
                    [$did]
                );

                // Merge PRS + DESE by year for dual-axis chart
                $prsMap = [];
                foreach ($prsByYear as $r) { $prsMap[(int)$r['yr']] = (int)$r['cnt']; }
                $deseMap = [];
                foreach ($deseRestraint as $r) { $deseMap[(int)substr($r['school_year'], 0, 4)] = $r; }
                $allYears = array_unique(array_merge(array_keys($prsMap), array_keys($deseMap)));
                sort($allYears);

                foreach ($allYears as $yr) {
                    $mergedByYear[] = [
                        'year'       => (string)$yr,
                        'prs_count'  => $prsMap[$yr] ?? 0,
                        'restraints' => (int)($deseMap[$yr]['restraints'] ?? 0),
                        'enrollment' => (int)($deseMap[$yr]['enrollment'] ?? 0),
                    ];
                }

                if (!empty($mergedByYear)) {
                    $chart = new Chart('combined-dual', 'bar');
                    $chart->setLabels(array_column($mergedByYear, 'year'));
                    $chart->addDataset('PRS Cases', array_column($mergedByYear, 'prs_count'), [
                        'backgroundColor' => 'rgba(255,90,31,0.7)',
                        'borderColor'     => '#ff5a1f',
                        'borderWidth'     => 1,
                        'borderRadius'    => 4,
                        'order'           => 2,
                    ]);
                    $chart->addDataset('Restraints', array_column($mergedByYear, 'restraints'), [
                        'type'            => 'line',
                        'yAxisID'         => 'y1',
                        'borderColor'     => '#60a5fa',
                        'backgroundColor' => 'rgba(96,165,250,0.1)',
                        'borderWidth'     => 2,
                        'tension'         => 0.3,
                        'order'           => 1,
                    ]);
                    $chart->setOption('scales.y.title.text', 'PRS Cases');
                    $chart->setOption('scales.y.title.display', true);
                    $chart->setOption('scales.y1.position', 'right');
                    $chart->setOption('scales.y1.title.text', 'Restraints');
                    $chart->setOption('scales.y1.title.display', true);
                    $chart->setOption('scales.y1.grid.drawOnChartArea', false);
                    $chart->setHeight(350);
                    $chartHtml = $chart->render();
                }
            }
        }

        View::render('data/combined', [
            'page_title'       => 'Combined Data — PRS + DESE',
            'page_description' => 'Side-by-side comparison of PRS filings and DESE-reported data for Massachusetts school districts.',
            'page_stylesheet'  => 'data',
            'districts'        => $districts,
            'districtCode'     => $districtCode,
            'selectedDistrict' => $selectedDistrict,
            'prsTotal'         => $prsTotal,
            'prsCases'         => $prsCases,
            'deseRestraint'    => $deseRestraint,
            'deseDiscipline'   => $deseDiscipline,
            'deseAttendance'   => $deseAttendance,
            'deseEnrollment'   => $deseEnrollment,
            'deseSped'         => $deseSped,
            'chartHtml'        => $chartHtml,
            'mergedByYear'     => $mergedByYear,
        ]);
    }

    /** GET /data/map — county-level choropleth map */
    public function map(array $params = []): void
    {
        $year = $_GET['school_year'] ?? null;
        if (!$year) {
            $year = Database::fetchColumn("SELECT MAX(school_year) FROM restraint_data");
        }

        $countyData = Database::fetchAllCached(
            "SELECT c.slug, c.county_name,
                    COALESCE(SUM(r.total_restraints), 0) AS total_restraints,
                    COALESCE(SUM(r.enrollment), 0) AS total_enrollment,
                    ROUND(COALESCE(SUM(r.total_restraints), 0) / NULLIF(SUM(r.enrollment), 0) * 100, 2) AS restraint_rate
             FROM counties c
             LEFT JOIN organizations o ON o.county_id = c.id
             LEFT JOIN restraint_data r ON r.org_id = o.id AND r.school_year = ?
             GROUP BY c.id, c.slug, c.county_name
             ORDER BY c.county_name",
            [$year], 300
        );

        // Compute both discrete and smooth color scales
        $values = [];
        foreach ($countyData as $row) { $values[] = (float)($row['restraint_rate'] ?? 0); }
        sort($values);
        $n = count($values);
        $p33 = $n > 0 ? $values[(int)($n * 0.33)] : 0;
        $p66 = $n > 0 ? $values[(int)($n * 0.66)] : 0;
        $min = $values[0] ?? 0;
        $max = $values[$n-1] ?? 1;
        $range = $max - $min ?: 1;

        $colors = [];
        $smoothColors = [];
        foreach ($countyData as $row) {
            $rate = (float)($row['restraint_rate'] ?? 0);
            // Discrete (percentile)
            $dcolor = $rate <= $p33 ? '#22c55e' : ($rate <= $p66 ? '#f59e0b' : '#ef4444');
            // Smooth (HSL gradient: green 120° → yellow 60° → red 0°)
            $ratio = ($rate - $min) / $range;
            $hue = round(120 * (1 - $ratio));
            $scolor = "hsl({$hue}, 70%, 45%)";

            $name = str_replace(' (county)', '', $row['county_name']);
            $colors[$row['slug']] = [
                'color' => $dcolor, 'smooth' => $scolor,
                'name' => $name, 'rate' => $rate,
                'restraints' => (int)($row['total_restraints'] ?? 0),
                'enrollment' => (int)($row['total_enrollment'] ?? 0),
            ];
            $smoothColors[$row['slug']] = $scolor;
        }

        $years = Database::fetchAllCached(
            "SELECT DISTINCT school_year FROM restraint_data ORDER BY school_year DESC", [], 300
        );

        View::render('data/map', [
            'page_title'      => 'MA County Restraint Map',
            'page_stylesheet' => 'data',
            'county_colors'   => $colors,
            'school_year'     => $year,
            'years'           => array_column($years, 'school_year'),
        ]);
    }

    /** GET /data/town-map — town/municipality-level choropleth */
    public function townMap(array $params = []): void
    {
        $year = $_GET['school_year'] ?? null;
        if (!$year) {
            $year = Database::fetchColumn("SELECT MAX(school_year) FROM restraint_data");
        }

        $townData = Database::fetchAllCached(
            "SELECT o.town,
                    COALESCE(SUM(r.total_restraints), 0) AS total_restraints,
                    COALESCE(SUM(r.enrollment), 0) AS total_enrollment,
                    ROUND(COALESCE(SUM(r.total_restraints), 0) / NULLIF(SUM(r.enrollment), 0) * 100, 2) AS restraint_rate
             FROM organizations o
             LEFT JOIN restraint_data r ON r.org_id = o.id AND r.school_year = ?
             WHERE o.town IS NOT NULL AND o.town != '' AND o.is_active = 1
             GROUP BY o.town
             HAVING total_enrollment > 0
             ORDER BY o.town",
            [$year], 300
        );

        $rates = [];
        foreach ($townData as $row) {
            $r = (float)($row['restraint_rate'] ?? 0);
            if ($r > 0) $rates[] = $r;
        }
        sort($rates);
        $n = count($rates);
        $p33 = $n > 0 ? $rates[(int)($n * 0.33)] : 0;
        $p66 = $n > 0 ? $rates[(int)($n * 0.66)] : 0;

        $colors = [];
        foreach ($townData as $row) {
            $rate = (float)($row['restraint_rate'] ?? 0);
            if ($rate == 0) {
                $color = '#2a2a2a';
            } else {
                $color = $rate <= $p33 ? '#22c55e' : ($rate <= $p66 ? '#f59e0b' : '#ef4444');
            }
            $slug = strtolower(str_replace([' ', "'", '.'], '-', $row['town']));
            $colors[$slug] = [
                'color' => $color,
                'name' => $row['town'],
                'rate' => $rate,
                'restraints' => (int)($row['total_restraints'] ?? 0),
                'enrollment' => (int)($row['total_enrollment'] ?? 0),
            ];
        }

        $years = Database::fetchAllCached(
            "SELECT DISTINCT school_year FROM restraint_data ORDER BY school_year DESC", [], 300
        );

        View::render('data/town-map', [
            'page_title'      => 'MA Town Restraint Map',
            'page_stylesheet' => 'data',
            'town_colors'     => $colors,
            'school_year'     => $year,
            'years'           => array_column($years, 'school_year'),
        ]);
    }

    /** GET /dev/chart-test — performance smoke test */
    public function chartTest(array $params = []): void
    {
        View::render('dev/chart-test', [
            'page_title'      => 'Chart Performance Test',
            'page_stylesheet' => 'data',
            'page_under_development' => true,
        ]);
    }

    /** GET /data/export — bulk data export page */
    public function export(array $params = []): void
    {
        View::render('data/export', [
            'page_title'      => 'Bulk Data Export',
            'page_stylesheet' => 'data',
        ]);
    }

    /** GET /data/help — documentation and help page */
    public function help(array $params = []): void
    {
        View::render('data/help', [
            'page_title'      => 'Data Help & Documentation',
            'page_stylesheet' => 'data',
        ]);
    }
}
