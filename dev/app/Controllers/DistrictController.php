<?php declare(strict_types=1);
namespace App\Controllers;

use App\Core\View;
use App\Core\Database;
use App\Components\Chart;

class DistrictController
{
    // ── Town → county slug mapping ──
    private const TOWN_COUNTY = [
        'Abington' => 'plymouth', 'Acton' => 'middlesex', 'Acushnet' => 'bristol',
        'Adams' => 'berkshire', 'Agawam' => 'hampden', 'Alford' => 'berkshire',
        'Amesbury' => 'essex', 'Amherst' => 'hampshire', 'Andover' => 'essex',
        'Aquinnah' => 'dukes', 'Arlington' => 'middlesex', 'Ashburnham' => 'worcester',
        'Ashby' => 'middlesex', 'Ashfield' => 'franklin', 'Ashland' => 'middlesex',
        'Athol' => 'worcester', 'Attleboro' => 'bristol', 'Auburn' => 'worcester',
        'Avon' => 'norfolk', 'Ayer' => 'middlesex', 'Barnstable' => 'barnstable',
        'Barre' => 'worcester', 'Becket' => 'berkshire', 'Bedford' => 'middlesex',
        'Belchertown' => 'hampshire', 'Bellingham' => 'norfolk', 'Belmont' => 'middlesex',
        'Berkley' => 'bristol', 'Berlin' => 'worcester', 'Bernardston' => 'franklin',
        'Beverly' => 'essex', 'Billerica' => 'middlesex', 'Blackstone' => 'worcester',
        'Blandford' => 'hampden', 'Bolton' => 'worcester', 'Boston' => 'suffolk',
        'Bourne' => 'barnstable', 'Boxborough' => 'middlesex', 'Boxford' => 'essex',
        'Boylston' => 'worcester', 'Braintree' => 'norfolk', 'Brewster' => 'barnstable',
        'Bridgewater' => 'plymouth', 'Brimfield' => 'hampden', 'Brockton' => 'plymouth',
        'Brookfield' => 'worcester', 'Brookline' => 'norfolk', 'Buckland' => 'franklin',
        'Burlington' => 'middlesex', 'Cambridge' => 'middlesex', 'Canton' => 'norfolk',
        'Carlisle' => 'middlesex', 'Carver' => 'plymouth', 'Charlemont' => 'franklin',
        'Charlton' => 'worcester', 'Chatham' => 'barnstable', 'Chelmsford' => 'middlesex',
        'Chelsea' => 'suffolk', 'Cheshire' => 'berkshire', 'Chester' => 'hampden',
        'Chesterfield' => 'hampshire', 'Chicopee' => 'hampden', 'Chilmark' => 'dukes',
        'Clarksburg' => 'berkshire', 'Clinton' => 'worcester', 'Cohasset' => 'norfolk',
        'Colrain' => 'franklin', 'Concord' => 'middlesex', 'Conway' => 'franklin',
        'Cummington' => 'hampshire', 'Dalton' => 'berkshire', 'Danvers' => 'essex',
        'Dartmouth' => 'bristol', 'Dedham' => 'norfolk', 'Deerfield' => 'franklin',
        'Dennis' => 'barnstable', 'Dighton' => 'bristol', 'Douglas' => 'worcester',
        'Dover' => 'norfolk', 'Dracut' => 'middlesex', 'Dudley' => 'worcester',
        'Dunstable' => 'middlesex', 'Duxbury' => 'plymouth', 'East Bridgewater' => 'plymouth',
        'East Brookfield' => 'worcester', 'East Longmeadow' => 'hampden',
        'Eastham' => 'barnstable', 'Easthampton' => 'hampshire', 'Easton' => 'bristol',
        'Edgartown' => 'dukes', 'Egremont' => 'berkshire', 'Erving' => 'franklin',
        'Essex' => 'essex', 'Everett' => 'middlesex', 'Fairhaven' => 'bristol',
        'Fall River' => 'bristol', 'Falmouth' => 'barnstable', 'Fitchburg' => 'worcester',
        'Florida' => 'berkshire', 'Foxborough' => 'norfolk', 'Framingham' => 'middlesex',
        'Franklin' => 'norfolk', 'Freetown' => 'bristol', 'Gardner' => 'worcester',
        'Georgetown' => 'essex', 'Gill' => 'franklin', 'Gloucester' => 'essex',
        'Goshen' => 'hampshire', 'Gosnold' => 'dukes', 'Grafton' => 'worcester',
        'Granby' => 'hampshire', 'Granville' => 'hampden', 'Great Barrington' => 'berkshire',
        'Greenfield' => 'franklin', 'Groton' => 'middlesex', 'Groveland' => 'essex',
        'Hadley' => 'hampshire', 'Halifax' => 'plymouth', 'Hamilton' => 'essex',
        'Hampden' => 'hampden', 'Hancock' => 'berkshire', 'Hanover' => 'plymouth',
        'Hanson' => 'plymouth', 'Hardwick' => 'worcester', 'Harvard' => 'worcester',
        'Harwich' => 'barnstable', 'Hatfield' => 'hampshire', 'Haverhill' => 'essex',
        'Hawley' => 'franklin', 'Heath' => 'franklin', 'Hingham' => 'plymouth',
        'Hinsdale' => 'berkshire', 'Holbrook' => 'norfolk', 'Holden' => 'worcester',
        'Holland' => 'hampden', 'Holliston' => 'middlesex', 'Holyoke' => 'hampden',
        'Hopedale' => 'worcester', 'Hopkinton' => 'middlesex', 'Hubbardston' => 'worcester',
        'Hudson' => 'middlesex', 'Hull' => 'plymouth', 'Huntington' => 'hampshire',
        'Ipswich' => 'essex', 'Kingston' => 'plymouth', 'Lakeville' => 'plymouth',
        'Lancaster' => 'worcester', 'Lanesborough' => 'berkshire', 'Lawrence' => 'essex',
        'Lee' => 'berkshire', 'Leicester' => 'worcester', 'Lenox' => 'berkshire',
        'Leominster' => 'worcester', 'Leverett' => 'franklin', 'Lexington' => 'middlesex',
        'Leyden' => 'franklin', 'Lincoln' => 'middlesex', 'Littleton' => 'middlesex',
        'Longmeadow' => 'hampden', 'Lowell' => 'middlesex', 'Ludlow' => 'hampden',
        'Lunenburg' => 'worcester', 'Lynn' => 'essex', 'Lynnfield' => 'essex',
        'Malden' => 'middlesex', 'Manchester-by-the-Sea' => 'essex', 'Mansfield' => 'bristol',
        'Marblehead' => 'essex', 'Marion' => 'plymouth', 'Marlborough' => 'middlesex',
        'Marshfield' => 'plymouth', 'Mashpee' => 'barnstable', 'Mattapoisett' => 'plymouth',
        'Maynard' => 'middlesex', 'Medfield' => 'norfolk', 'Medford' => 'middlesex',
        'Medway' => 'norfolk', 'Melrose' => 'middlesex', 'Mendon' => 'worcester',
        'Merrimac' => 'essex', 'Methuen' => 'essex', 'Middleborough' => 'plymouth',
        'Middlefield' => 'hampshire', 'Middleton' => 'essex', 'Milford' => 'worcester',
        'Millbury' => 'worcester', 'Millis' => 'norfolk', 'Millville' => 'worcester',
        'Milton' => 'norfolk', 'Monroe' => 'franklin', 'Monson' => 'hampden',
        'Montague' => 'franklin', 'Monterey' => 'berkshire', 'Montgomery' => 'hampden',
        'Mount Washington' => 'berkshire', 'Nahant' => 'essex', 'Nantucket' => 'nantucket',
        'Natick' => 'middlesex', 'Needham' => 'norfolk', 'New Ashford' => 'berkshire',
        'New Bedford' => 'bristol', 'New Braintree' => 'worcester',
        'New Marlborough' => 'berkshire', 'New Salem' => 'franklin',
        'Newbury' => 'essex', 'Newburyport' => 'essex', 'Newton' => 'middlesex',
        'Norfolk' => 'norfolk', 'North Adams' => 'berkshire', 'North Andover' => 'essex',
        'North Attleborough' => 'bristol', 'North Brookfield' => 'worcester',
        'North Reading' => 'middlesex', 'Northampton' => 'hampshire',
        'Northborough' => 'worcester', 'Northbridge' => 'worcester', 'Northfield' => 'franklin',
        'Norton' => 'bristol', 'Norwell' => 'plymouth', 'Norwood' => 'norfolk',
        'Oak Bluffs' => 'dukes', 'Oakham' => 'worcester', 'Orange' => 'franklin',
        'Orleans' => 'barnstable', 'Otis' => 'berkshire', 'Oxford' => 'worcester',
        'Palmer' => 'hampden', 'Paxton' => 'worcester', 'Peabody' => 'essex',
        'Pelham' => 'hampshire', 'Pembroke' => 'plymouth', 'Pepperell' => 'middlesex',
        'Peru' => 'berkshire', 'Petersham' => 'worcester', 'Phillipston' => 'worcester',
        'Pittsfield' => 'berkshire', 'Plainfield' => 'hampshire', 'Plainville' => 'norfolk',
        'Plymouth' => 'plymouth', 'Plympton' => 'plymouth', 'Princeton' => 'worcester',
        'Provincetown' => 'barnstable', 'Quincy' => 'norfolk', 'Randolph' => 'norfolk',
        'Raynham' => 'bristol', 'Reading' => 'middlesex', 'Rehoboth' => 'bristol',
        'Revere' => 'suffolk', 'Richmond' => 'berkshire', 'Rochester' => 'plymouth',
        'Rockland' => 'plymouth', 'Rockport' => 'essex', 'Rowe' => 'franklin',
        'Rowley' => 'essex', 'Royalston' => 'worcester', 'Russell' => 'hampden',
        'Rutland' => 'worcester', 'Salem' => 'essex', 'Salisbury' => 'essex',
        'Sandisfield' => 'berkshire', 'Sandwich' => 'barnstable', 'Saugus' => 'essex',
        'Savoy' => 'berkshire', 'Scituate' => 'plymouth', 'Seekonk' => 'bristol',
        'Sharon' => 'norfolk', 'Sheffield' => 'berkshire', 'Shelburne' => 'franklin',
        'Sherborn' => 'middlesex', 'Shirley' => 'middlesex', 'Shrewsbury' => 'worcester',
        'Shutesbury' => 'franklin', 'Somerset' => 'bristol', 'Somerville' => 'middlesex',
        'South Hadley' => 'hampshire', 'Southampton' => 'hampshire',
        'Southborough' => 'worcester', 'Southbridge' => 'worcester', 'Southwick' => 'hampden',
        'Spencer' => 'worcester', 'Springfield' => 'hampden', 'Sterling' => 'worcester',
        'Stockbridge' => 'berkshire', 'Stoneham' => 'middlesex', 'Stoughton' => 'norfolk',
        'Stow' => 'middlesex', 'Sturbridge' => 'worcester', 'Sudbury' => 'middlesex',
        'Sunderland' => 'franklin', 'Sutton' => 'worcester', 'Swampscott' => 'essex',
        'Swansea' => 'bristol', 'Taunton' => 'bristol', 'Templeton' => 'worcester',
        'Tewksbury' => 'middlesex', 'Tisbury' => 'dukes', 'Tolland' => 'hampden',
        'Topsfield' => 'essex', 'Townsend' => 'middlesex', 'Truro' => 'barnstable',
        'Tyngsborough' => 'middlesex', 'Tyringham' => 'berkshire', 'Upton' => 'worcester',
        'Uxbridge' => 'worcester', 'Wakefield' => 'middlesex', 'Wales' => 'hampden',
        'Walpole' => 'norfolk', 'Waltham' => 'middlesex', 'Ware' => 'hampshire',
        'Wareham' => 'plymouth', 'Warren' => 'worcester', 'Warwick' => 'franklin',
        'Washington' => 'berkshire', 'Watertown' => 'middlesex', 'Wayland' => 'middlesex',
        'Webster' => 'worcester', 'Wellesley' => 'norfolk', 'Wellfleet' => 'barnstable',
        'Wendell' => 'franklin', 'Wenham' => 'essex', 'West Boylston' => 'worcester',
        'West Bridgewater' => 'plymouth', 'West Brookfield' => 'worcester',
        'West Newbury' => 'essex', 'West Springfield' => 'hampden',
        'West Stockbridge' => 'berkshire', 'West Tisbury' => 'dukes',
        'Westborough' => 'worcester', 'Westfield' => 'hampden', 'Westford' => 'middlesex',
        'Westhampton' => 'hampshire', 'Westminster' => 'worcester', 'Weston' => 'middlesex',
        'Westport' => 'bristol', 'Westwood' => 'norfolk', 'Weymouth' => 'norfolk',
        'Whately' => 'franklin', 'Whitman' => 'plymouth', 'Wilbraham' => 'hampden',
        'Williamsburg' => 'hampshire', 'Williamstown' => 'berkshire', 'Wilmington' => 'middlesex',
        'Winchendon' => 'worcester', 'Winchester' => 'middlesex', 'Windsor' => 'berkshire',
        'Winthrop' => 'suffolk', 'Woburn' => 'middlesex', 'Worcester' => 'worcester',
        'Worthington' => 'hampshire', 'Wrentham' => 'norfolk', 'Yarmouth' => 'barnstable',
    ];

    // ── District list with sparklines, badges, sort & filter ──
    public function list(array $params = []): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = DEFAULT_PER_PAGE;
        $offset = ($page - 1) * $perPage;

        // Sort
        $sort = $_GET['sort'] ?? 'name';
        $sortCol = match ($sort) {
            'code'     => 'o.org_code',
            'town'     => 'o.town',
            'enrollment' => 'total_enrollment DESC',
            'prs'      => 'prs_count DESC',
            'attendance' => 'attendance_rate DESC NULLS LAST',
            default    => 'o.org_name',
        };

        // Filter
        $filter = $_GET['filter'] ?? '';
        $filterClause = '';
        $filterParams = [];
        $stateAvgRestraint = 6.5; // approximate state average restraint rate per 100

        if ($filter === 'high_restraint') {
            $filterClause = "AND latest_restraint.rate_per_100 > ?";
            $filterParams = [$stateAvgRestraint];
        } elseif ($filter === 'active_prs') {
            $filterClause = "AND (SELECT COUNT(*) FROM prs_cases pc WHERE pc.org_id = o.id AND pc.current_status NOT IN ('closed','dismissed')) > 0";
        }

        // Count
        $countSql = "SELECT COUNT(*) FROM organizations o
             LEFT JOIN (SELECT d.id AS did FROM organizations d
                 JOIN organizations s ON s.parent_org_id = d.id
                 JOIN restraint_data r ON r.org_id = s.id
                 GROUP BY d.id) sub ON sub.did = o.id
             WHERE o.org_type = 'Public School District' AND o.is_active = 1 $filterClause";
        $total = (int)Database::fetchColumn($countSql, $filterParams);

        $districts = Database::fetchAll(
            "SELECT o.id, o.org_code, o.org_name, o.town, o.grade_span,
                    COALESCE(demog.total_enrollment, 0) AS total_enrollment,
                    COALESCE(demog.attendance_rate, 0) AS attendance_rate,
                    COALESCE(demog.sped_pct, 0) AS sped_pct,
                    (SELECT COUNT(*) FROM prs_cases pc WHERE pc.org_id = o.id) AS prs_count
             FROM organizations o
             LEFT JOIN (
                 SELECT d.id AS did,
                        SUM(s_enr.enrollment) AS total_enrollment,
                        AVG(s_att.attendance_rate) AS attendance_rate,
                        AVG(s_dem.sped_pct) AS sped_pct
                 FROM organizations d
                 JOIN organizations s ON s.parent_org_id = d.id
                 LEFT JOIN (
                     SELECT org_id, SUM(enrollment) AS enrollment
                     FROM restraint_data
                     WHERE school_year = (SELECT MAX(school_year) FROM restraint_data)
                     GROUP BY org_id
                 ) s_enr ON s_enr.org_id = s.id
                 LEFT JOIN (
                     SELECT org_id, attendance_rate
                     FROM attendance_data
                     WHERE school_year = (SELECT MAX(school_year) FROM attendance_data)
                 ) s_att ON s_att.org_id = d.id
                 LEFT JOIN (
                     SELECT org_id, sped_pct
                     FROM enrollment_data
                     WHERE school_year = (SELECT MAX(school_year) FROM enrollment_data)
                 ) s_dem ON s_dem.org_id = d.id
                 GROUP BY d.id
             ) demog ON demog.did = o.id
             WHERE o.org_type = 'Public School District' AND o.is_active = 1
             $filterClause
             ORDER BY $sortCol
             LIMIT ? OFFSET ?",
            array_merge($filterParams, [$perPage, $offset])
        );

        // Bulk fetch sparkline data for all visible districts
        $districtIds = array_column($districts, 'id');
        $sparklines = [];
        if (!empty($districtIds)) {
            $placeholders = implode(',', array_fill(0, count($districtIds), '?'));
            $sparkRows = Database::fetchAll(
                "SELECT d.id AS district_id, r2.school_year,
                        SUM(r2.total_restraints) AS total_restraints
                 FROM organizations d
                 JOIN organizations s ON s.parent_org_id = d.id
                 JOIN restraint_data r2 ON r2.org_id = s.id
                 WHERE d.id IN ($placeholders)
                 GROUP BY d.id, r2.school_year
                 ORDER BY d.id, r2.school_year ASC",
                array_values($districtIds)
            );
            foreach ($sparkRows as $row) {
                $sparklines[$row['district_id']][] = (int)($row['total_restraints'] ?? 0);
            }
        }

        // Badge computation
        $badges = [];
        $latestYear = Database::fetchColumn(
            "SELECT MAX(school_year) FROM restraint_data"
        );
        $prevYear = (string)((int)substr($latestYear, 0, 4) - 1) . '-' . substr($latestYear, 5, 4);
        if (!empty($districtIds)) {
            $badgeSql = "SELECT d.id AS district_id,
                    CASE WHEN SUM(r.enrollment) > 0
                         THEN (SUM(r.total_restraints) / SUM(r.enrollment)) * 100
                         ELSE 0 END AS restraint_rate,
                    SUM(CASE WHEN r.school_year = ? THEN r.total_restraints ELSE 0 END) AS curr_restraints,
                    SUM(CASE WHEN r.school_year = ? THEN r.total_restraints ELSE 0 END) AS prev_restraints
                 FROM organizations d
                 JOIN organizations s ON s.parent_org_id = d.id
                 JOIN restraint_data r ON r.org_id = s.id
                 WHERE d.id IN ($placeholders)
                 GROUP BY d.id";
            $badgeRows = Database::fetchAll($badgeSql,
                array_merge([$latestYear, $prevYear], array_values($districtIds))
            );
            foreach ($badgeRows as $row) {
                $b = [];
                if ($row['restraint_rate'] < $stateAvgRestraint && $row['restraint_rate'] > 0) {
                    $b[] = ['label' => 'Low Restraint', 'class' => 'badge-green'];
                }
                if ($row['curr_restraints'] < $row['prev_restraints'] && $row['prev_restraints'] > 0) {
                    $b[] = ['label' => 'Improving', 'class' => 'badge-blue'];
                }
                if ($row['curr_restraints'] > $row['prev_restraints'] && $row['prev_restraints'] > 0) {
                    $b[] = ['label' => 'Declining', 'class' => 'badge-red'];
                }
                $badges[$row['district_id']] = $b;
            }
        }

        // Attendance badges
        foreach ($districts as &$d) {
            $did = $d['id'];
            if (($d['attendance_rate'] ?? 0) > 95) {
                $badges[$did] = array_merge($badges[$did] ?? [], [['label' => 'High Attendance', 'class' => 'badge-green']]);
            }
            $d['sparkline'] = $sparklines[$did] ?? [];
            $d['badges'] = $badges[$did] ?? [];
            // limit to 3
            $d['badges'] = array_slice($d['badges'], 0, 3);
        }
        unset($d);

        $pagination = paginate($total, $perPage, $page);

        View::render('district-list', [
            'page_title'      => 'School Districts — Parent Data Force',
            'page_stylesheet' => 'districts',
            'districts'       => $districts,
            'pagination'      => $pagination,
            'total'           => $total,
            'current_sort'    => $sort,
            'current_filter'  => $filter,
        ]);
    }

    // ── District detail with hero stats, charts, gauges, timeline ──
    public function show(array $params = []): void
    {
        $code = strtoupper($params['slug'] ?? '');
        $district = Database::fetch(
            "SELECT o.*, c.county_name, c.slug AS county_slug
             FROM organizations o
             LEFT JOIN counties c ON o.county_id = c.id
             WHERE o.org_code = ? AND o.is_active = 1", [$code]
        );

        if (!$district) {
            http_response_code(404);
            View::render('errors/404', ['page_title' => 'District Not Found']);
            return;
        }

        // Compute county from town if county_id not set
        if (empty($district['county_name']) && !empty($district['town'])) {
            $district['county_name'] = $this->countyForTown($district['town']);
        }

        // Hero stats: schools count + total enrollment
        $schoolCount = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM organizations WHERE parent_org_id = ? AND is_active = 1",
            [$district['id']]
        );
        $enrollmentTotal = (int)Database::fetchColumn(
            "SELECT SUM(r.enrollment)
             FROM restraint_data r
             JOIN organizations s ON r.org_id = s.id
             WHERE s.parent_org_id = ? AND r.school_year = (SELECT MAX(school_year) FROM restraint_data)",
            [$district['id']]
        );

        // Cases
        $cases = Database::fetchAll(
            "SELECT case_number, title, slug, status, filed_date
             FROM cases WHERE org_id = ? AND is_active = 1
             ORDER BY filed_date DESC LIMIT 10",
            [$district['id']]
        );

        // ── Restraint trend data (5 years, aggregate from schools) ──
        $restraintData = Database::fetchAll(
            "SELECT r.school_year,
                    SUM(r.enrollment) AS enrollment,
                    SUM(r.total_restraints) AS total_restraints,
                    SUM(r.students_restrained) AS students_restrained,
                    SUM(r.total_injuries) AS total_injuries
             FROM restraint_data r
             JOIN organizations s ON r.org_id = s.id
             WHERE s.parent_org_id = ?
             GROUP BY r.school_year ORDER BY r.school_year DESC LIMIT 5",
            [$district['id']]
        );
        // If no school-level data, check district-level
        if (empty($restraintData)) {
            $restraintData = Database::fetchAll(
                "SELECT school_year, enrollment, total_restraints, students_restrained, total_injuries
                 FROM restraint_data WHERE org_id = ?
                 ORDER BY school_year DESC LIMIT 5",
                [$district['id']]
            );
        }

        // ── Demographics (latest year) ──
        $enrollmentData = Database::fetch(
            "SELECT school_year, high_needs_pct, el_pct, low_income_pct, sped_pct
             FROM enrollment_data WHERE org_id = ?
             ORDER BY school_year DESC LIMIT 1",
            [$district['id']]
        );

        // State percentile data for gauge coloring
        $statePercentiles = $this->statePercentiles();

        // ── SPED outcomes (latest) ──
        $spedData = Database::fetch(
            "SELECT school_year, sped_grad_rate, sped_dropout_rate, lre_full_incl_pct
             FROM sped_results WHERE org_id = ?
             ORDER BY school_year DESC LIMIT 1",
            [$district['id']]
        );

        // State averages for SPED comparison
        $spedYear = $spedData['school_year'] ?? '';
        $stateSpedAverages = [];
        if ($spedYear) {
            $stateSpedAverages = Database::fetch(
                "SELECT AVG(sped_grad_rate) AS avg_grad,
                        AVG(sped_dropout_rate) AS avg_dropout,
                        AVG(lre_full_incl_pct) AS avg_incl
                 FROM sped_results WHERE school_year = ?",
                [$spedYear]
            );
        }

        // ── Timeline: PRS cases + documents ──
        $prsCases = Database::fetchAll(
            "SELECT pc.prs_number, pc.case_title, pc.filing_date, pc.current_status, pc.findings_issued_date, pc.closure_date, pc.resolution_type
             FROM prs_cases pc WHERE pc.org_id = ?
             ORDER BY COALESCE(pc.filing_date, pc.created_at) DESC LIMIT 20",
            [$district['id']]
        );

        // Documents for timeline
        $documents = Database::fetchAll(
            "SELECT d.title, d.document_date, d.doc_type, d.file_url, d.doc_family
             FROM documents d WHERE d.org_id = ?
             ORDER BY d.document_date DESC LIMIT 20",
            [$district['id']]
        );

        // Merge PRS cases + documents into a single timeline
        $timeline = [];
        foreach ($prsCases as $c) {
            $timeline[] = [
                'type'  => 'prs',
                'date'  => $c['filing_date'],
                'label' => $c['prs_number'] . ': ' . ($c['case_title'] ?? 'Untitled'),
                'status'=> $c['current_status'],
                'resolution' => $c['resolution_type'],
                'closure_date' => $c['closure_date'],
                'findings_date' => $c['findings_issued_date'],
            ];
        }
        foreach ($documents as $d) {
            $timeline[] = [
                'type'  => 'document',
                'date'  => $d['document_date'],
                'label' => $d['title'] ?? 'Untitled Document',
                'doc_type' => $d['doc_type'],
                'doc_family' => $d['doc_family'],
                'url'   => $d['file_url'],
            ];
        }
        usort($timeline, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));

        // ── Similar districts ──
        $similarDistricts = $this->getSimilarDistricts($district['id'], (int)$enrollmentTotal);

        // ── Build restraint trend chart ──
        $trendChart = '';
        if (!empty($restraintData)) {
            $trendData = array_reverse($restraintData);
            $chart = new Chart('dist-restraint-trend', 'bar');
            $chart->setLabels(array_column($trendData, 'school_year'));
            $chart->addDataset('Total Restraints', array_column($trendData, 'total_restraints'), ['palette' => 'orange']);
            $chart->setHeight(300);
            $chart->setOption('maintainAspectRatio', false);
            $chart->setOption('scales.y.title.display', true);
            $chart->setOption('scales.y.title.text', 'Restraints');
            $trendChart = $chart->render();
        }

        // ── SPED outcomes comparison chart ──
        $spedChart = '';
        if ($spedData) {
            $chart = new Chart('dist-sped-compare', 'bar');
            $chart->setLabels(['Grad Rate %', 'Dropout Rate %', 'Inclusion %']);
            $chart->addDataset(
                h($district['org_name']),
                [
                    (float)($spedData['sped_grad_rate'] ?? 0),
                    (float)($spedData['sped_dropout_rate'] ?? 0),
                    (float)($spedData['lre_full_incl_pct'] ?? 0),
                ],
                ['palette' => 'orange']
            );
            $chart->addDataset('MA Average', [
                (float)($stateSpedAverages['avg_grad'] ?? 0),
                (float)($stateSpedAverages['avg_dropout'] ?? 0),
                (float)($stateSpedAverages['avg_incl'] ?? 0),
            ], ['palette' => 'cool']);
            $chart->setHeight(300);
            $chart->setOption('maintainAspectRatio', false);
            $spedChart = $chart->render();
        }

        // ── Attendance for badge ──
        $attendance = Database::fetch(
            "SELECT attendance_rate, chronically_absent_10_pct FROM attendance_data
             WHERE org_id = ? ORDER BY school_year DESC LIMIT 1",
            [$district['id']]
        );

        View::render('district', [
            'page_title'       => h($district['org_name']) . ' — Parent Data Force',
            'page_stylesheet'  => 'districts',
            'district'         => $district,
            'school_count'     => $schoolCount,
            'total_enrollment' => $enrollmentTotal,
            'hero_stats'       => [
                ['label' => 'Org Code', 'value' => $district['org_code']],
                ['label' => 'Town', 'value' => $district['town'] ?? '—'],
                ['label' => 'Schools', 'value' => number_format($schoolCount)],
                ['label' => 'Enrollment', 'value' => number_format($enrollmentTotal)],
            ],
            'cases'            => $cases,
            'restraintData'    => $restraintData,
            'trendChart'       => $trendChart,
            'enrollmentData'   => $enrollmentData,
            'spedData'         => $spedData,
            'spedChart'        => $spedChart,
            'stateSpedAverages'=> $stateSpedAverages,
            'statePercentiles' => $statePercentiles,
            'timeline'         => $timeline,
            'similarDistricts' => $similarDistricts,
            'attendance'       => $attendance,
        ]);
    }

    // ── County list ──
    public function countiesList(array $params = []): void
    {
        $counties = Database::fetchAll(
            "SELECT c.id, c.county_name, c.slug,
                    COUNT(DISTINCT o.id) AS district_count,
                    COALESCE(SUM((SELECT SUM(r.total_restraints)
                         FROM restraint_data r JOIN organizations s ON r.org_id = s.id
                         WHERE s.parent_org_id = o.id AND r.school_year = (SELECT MAX(school_year) FROM restraint_data))), 0) AS total_restraints,
                    COALESCE(SUM((SELECT SUM(r.enrollment)
                         FROM restraint_data r JOIN organizations s ON r.org_id = s.id
                         WHERE s.parent_org_id = o.id AND r.school_year = (SELECT MAX(school_year) FROM restraint_data))), 0) AS total_enrollment
             FROM counties c
             LEFT JOIN organizations o ON o.county_id = c.id AND o.org_type = 'Public School District' AND o.is_active = 1
             GROUP BY c.id, c.county_name, c.slug
             ORDER BY c.county_name"
        );

        View::render('counties/list', [
            'page_title'      => 'Counties — Parent Data Force',
            'page_stylesheet' => 'districts',
            'counties'        => $counties,
        ]);
    }

    // ── County detail ──
    public function countyShow(array $params = []): void
    {
        $slug = $params['slug'] ?? '';
        $county = Database::fetch(
            "SELECT * FROM counties WHERE slug = ?", [$slug]
        );
        if (!$county) {
            http_response_code(404);
            View::render('errors/404', ['page_title' => 'County Not Found']);
            return;
        }

        // Districts in this county
        $districts = Database::fetchAll(
            "SELECT org_code, org_name, town FROM organizations
             WHERE county_id = ? AND org_type = 'Public School District' AND is_active = 1
             ORDER BY org_name",
            [$county['id']]
        );
        $districtIds = array_column($districts, 'id') ?: [];
        // Re-fetch with IDs
        $orgRows = Database::fetchAll(
            "SELECT id, org_code, org_name, town FROM organizations
             WHERE county_id = ? AND org_type = 'Public School District' AND is_active = 1
             ORDER BY org_name",
            [$county['id']]
        );
        $districtIds = array_column($orgRows, 'id');
        $districtCount = count($orgRows);

        // Aggregate stats for the county
        $stats = [];
        if (!empty($districtIds)) {
            $pl = implode(',', array_fill(0, count($districtIds), '?'));
            $latestYear = Database::fetchColumn("SELECT MAX(school_year) FROM restraint_data");
            $stats = Database::fetch(
                "SELECT SUM(sr.enrollment) AS total_enrollment,
                        SUM(sr.total_restraints) AS total_restraints,
                        SUM(sr.students_restrained) AS students_restrained
                 FROM restraint_data sr
                 JOIN organizations s ON sr.org_id = s.id
                 WHERE s.parent_org_id IN ($pl) AND sr.school_year = ?",
                array_merge($districtIds, [$latestYear])
            );
        }
        $stats = $stats ?: ['total_enrollment' => 0, 'total_restraints' => 0, 'students_restrained' => 0];

        // County restraint trend (last 5 years)
        $trendData = [];
        if (!empty($districtIds)) {
            $pl = implode(',', array_fill(0, count($districtIds), '?'));
            $trendData = Database::fetchAll(
                "SELECT r.school_year, SUM(r.total_restraints) AS total_restraints
                 FROM restraint_data r
                 JOIN organizations s ON r.org_id = s.id
                 WHERE s.parent_org_id IN ($pl)
                 GROUP BY r.school_year ORDER BY r.school_year DESC LIMIT 5",
                $districtIds
            );
        }

        // County comparison chart
        $trendChart = '';
        if (!empty($trendData)) {
            $tdReversed = array_reverse($trendData);
            $chart = new Chart('county-restraint-trend', 'bar');
            $chart->setLabels(array_column($tdReversed, 'school_year'));
            $chart->addDataset('Restraints', array_map('intval', array_column($tdReversed, 'total_restraints')), ['palette' => 'orange']);
            $chart->setHeight(300);
            $chart->setOption('maintainAspectRatio', false);
            $trendChart = $chart->render();
        }

        // All counties comparison bar
        $allCountyStats = Database::fetchAll(
            "SELECT c.county_name, COALESCE(SUM(sub.restraints), 0) AS restraints
             FROM counties c
             LEFT JOIN (
                 SELECT o.county_id, SUM(r.total_restraints) AS restraints
                 FROM restraint_data r
                 JOIN organizations s ON r.org_id = s.id
                 JOIN organizations o ON s.parent_org_id = o.id
                 WHERE r.school_year = ?
                 GROUP BY o.county_id
             ) sub ON sub.county_id = c.id
             GROUP BY c.id, c.county_name
             ORDER BY c.county_name",
            [$latestYear ?? '2023-2024']
        );
        $compareChart = '';
        if (!empty($allCountyStats)) {
            $chart = new Chart('county-compare', 'bar');
            $chart->setLabels(array_column($allCountyStats, 'county_name'));
            $chart->addDataset('Total Restraints', array_map('intval', array_column($allCountyStats, 'restraints')), ['palette' => 'cool']);
            $chart->setHeight(400);
            $chart->setOption('maintainAspectRatio', false);
            $chart->setOption('indexAxis', 'y');
            $compareChart = $chart->render();
        }


        // ── Town-level breakdown ──
        $townBreakdown = [];
        if (!empty($districtIds)) {
            $pl = implode(',', array_fill(0, count($districtIds), '?'));
            $townBreakdown = Database::fetchAll(
                "SELECT o.town,
                        COALESCE(SUM(r.enrollment), 0) AS enrollment,
                        COALESCE(SUM(r.total_restraints), 0) AS restraints,
                        ROUND(COALESCE(SUM(r.total_restraints), 0) / NULLIF(SUM(r.enrollment), 0) * 100, 2) AS rate
                 FROM organizations s
                 JOIN restraint_data r ON r.org_id = s.id AND r.school_year = ?
                 JOIN organizations o ON s.parent_org_id = o.id
                 WHERE o.id IN ($pl)
                 GROUP BY o.town
                 HAVING enrollment > 0
                 ORDER BY rate DESC",
                array_merge([$latestYear], $districtIds)
            );
        }

        // ── PRS stats for this county ──
        $prsStats = ['total' => 0, 'open' => 0];
        if (!empty($districtIds)) {
            $pl = implode(',', array_fill(0, count($districtIds), '?'));
            $prsStats = Database::fetch(
                "SELECT COUNT(*) AS total,
                        COUNT(CASE WHEN pc.current_status != 'closed' THEN 1 END) AS open
                 FROM prs_cases pc
                 JOIN organizations o ON pc.org_id = o.id
                 WHERE o.id IN ($pl)",
                $districtIds
            ) ?: ['total' => 0, 'open' => 0];
        }

        // ── PRS filing trend ──
        $prsTrendData = [];
        if (!empty($districtIds)) {
            $pl = implode(',', array_fill(0, count($districtIds), '?'));
            $prsTrendData = Database::fetchAll(
                "SELECT YEAR(pc.filing_date) AS yr, COUNT(*) AS cnt
                 FROM prs_cases pc
                 JOIN organizations o ON pc.org_id = o.id
                 WHERE o.id IN ($pl) AND pc.filing_date IS NOT NULL
                 GROUP BY yr ORDER BY yr DESC LIMIT 7",
                $districtIds
            );
        }
        $prsTrendChart = '';
        if (!empty($prsTrendData)) {
            $ptr = array_reverse($prsTrendData);
            $pc = new Chart('county-prs-trend', 'line');
            $pc->setLabels(array_column($ptr, 'yr'));
            $pc->addDataset('PRS Cases', array_map('intval', array_column($ptr, 'cnt')), [
                'borderColor' => '#ff5a1f', 'backgroundColor' => 'rgba(255,90,31,0.1)', 'fill' => true
            ]);
            $pc->setOption('plugins.legend.display', false);
            $pc->setHeight(250);
            $prsTrendChart = $pc->render();
        }
        View::render('counties/detail', [
            'page_title'      => $county['county_name'] . ' County — Parent Data Force',
            'page_stylesheet' => 'districts',
            'county'          => $county,
            'districts'       => $orgRows,
            'district_count'  => $districtCount,
            'stats'           => $stats,
            'trendChart'      => $trendChart,
            'compareChart'    => $compareChart,
            'townBreakdown'   => $townBreakdown,
            'prsStats'        => $prsStats,
            'prsTrendChart'   => $prsTrendChart,
            'prsTrendData'    => $prsTrendData,
        ]);
    }

    // ── Helpers ──

    private function countyForTown(string $town): string
    {
        $town = ucwords(strtolower(trim($town)));
        return self::TOWN_COUNTY[$town] ?? '';
    }

    private function statePercentiles(): array
    {
        static $cache = null;
        if ($cache !== null) return $cache;

        $latestYear = Database::fetchColumn("SELECT MAX(school_year) FROM enrollment_data");
        $cache = Database::fetch(
            "SELECT
                AVG(sped_pct) AS sped_avg,
                STDDEV_POP(sped_pct) AS sped_std,
                AVG(el_pct) AS el_avg,
                STDDEV_POP(el_pct) AS el_std,
                AVG(low_income_pct) AS li_avg,
                STDDEV_POP(low_income_pct) AS li_std,
                AVG(high_needs_pct) AS hn_avg,
                STDDEV_POP(high_needs_pct) AS hn_std
             FROM enrollment_data WHERE school_year = ?",
            [$latestYear]
        ) ?: [];
        return $cache;
    }

    private function getSimilarDistricts(int $districtId, int $enrollment): array
    {
        // Find districts with similar enrollment ±50%, limit 5
        $low = (int)($enrollment * 0.5);
        $high = (int)($enrollment * 1.5);
        if ($low === $high) { $low = max(0, $low - 500); $high += 500; }

        return Database::fetchAll(
            "SELECT o.id, o.org_code, o.org_name, o.town,
                    COALESCE(SUM(r.enrollment), 0) AS enroll
             FROM organizations o
             JOIN organizations s ON s.parent_org_id = o.id
             JOIN restraint_data r ON r.org_id = s.id
             WHERE o.id != ? AND o.org_type = 'Public School District' AND o.is_active = 1
               AND r.school_year = (SELECT MAX(school_year) FROM restraint_data)
             GROUP BY o.id, o.org_code, o.org_name, o.town
             HAVING COALESCE(SUM(r.enrollment), 0) BETWEEN ? AND ?
             ORDER BY ABS(COALESCE(SUM(r.enrollment), 0) - ?) ASC
             LIMIT 5",
            [$districtId, $low, $high, $enrollment]
        );
    }
}
