<?php declare(strict_types=1);
/**
 * PRS Tracker Migration Script
 * Creates prs_cases, prs_events, prs_findings, prs_categories tables
 * and migrates data from prs_intakes.
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;

echo "=== PRS Tracker Migration ===\n\n";

// ── Step 1-4: Create Tables ──
echo "Creating tables...\n";

Database::execute("
CREATE TABLE IF NOT EXISTS prs_cases (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prs_number              VARCHAR(50)     NOT NULL UNIQUE,
    org_id                  INT UNSIGNED    NULL,
    case_title              VARCHAR(512)    NULL,
    case_description        TEXT            NULL,
    filing_date             DATE            NULL,
    acceptance_date         DATE            NULL,
    investigation_start     DATE            NULL,
    findings_issued_date    DATE            NULL,
    closure_date            DATE            NULL,
    current_status          ENUM('filed','accepted','investigating','findings','closed','appealed') NOT NULL DEFAULT 'filed',
    resolution_type         ENUM('substantiated','unsubstantiated','partially_substantiated','resolved','withdrawn','dismissed') NULL,
    complainant_type        VARCHAR(100)    NULL,
    allegations             JSON            NULL,
    findings_summary        TEXT            NULL,
    corrective_actions      TEXT            NULL,
    deso_reference_url      VARCHAR(1024)   NULL,
    created_at              DATETIME        NOT NULL DEFAULT NOW(),
    updated_at              DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    INDEX idx_prs_number (prs_number),
    INDEX idx_org_id (org_id),
    INDEX idx_current_status (current_status),
    INDEX idx_filing_date (filing_date),
    CONSTRAINT fk_prs_cases_org FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "  prs_cases ✓\n";

Database::execute("
CREATE TABLE IF NOT EXISTS prs_events (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prs_case_id         INT UNSIGNED    NOT NULL,
    event_date          DATE            NOT NULL,
    event_type          ENUM('filed','acknowledged','assigned','extension_requested','extension_granted','investigation_opened','interview_conducted','site_visit','preliminary_findings','district_response','findings_issued','corrective_action_ordered','compliance_verified','closed','appealed','reopened') NOT NULL,
    event_description   TEXT            NULL,
    actor               VARCHAR(255)    NULL,
    created_at          DATETIME        NOT NULL DEFAULT NOW(),
    INDEX idx_prs_case_id (prs_case_id),
    INDEX idx_event_date (event_date),
    INDEX idx_event_type (event_type),
    CONSTRAINT fk_prs_events_case FOREIGN KEY (prs_case_id) REFERENCES prs_cases(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "  prs_events ✓\n";

Database::execute("
CREATE TABLE IF NOT EXISTS prs_findings (
    id                          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prs_case_id                 INT UNSIGNED    NOT NULL,
    finding_number              INT             NOT NULL DEFAULT 1,
    allegation_category         VARCHAR(100)    NULL,
    allegation_subcategory      VARCHAR(100)    NULL,
    finding                     ENUM('substantiated','unsubstantiated','partially_substantiated') NULL,
    finding_detail              TEXT            NULL,
    cited_regulation            VARCHAR(255)    NULL,
    corrective_action_ordered   TEXT            NULL,
    corrective_action_status    ENUM('pending','in_progress','completed','overdue') NULL DEFAULT 'pending',
    corrective_action_deadline  DATE            NULL,
    compliance_verified_date    DATE            NULL,
    created_at                  DATETIME        NOT NULL DEFAULT NOW(),
    INDEX idx_prs_case_id (prs_case_id),
    CONSTRAINT fk_prs_findings_case FOREIGN KEY (prs_case_id) REFERENCES prs_cases(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "  prs_findings ✓\n";

Database::execute("
CREATE TABLE IF NOT EXISTS prs_categories (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_name   VARCHAR(100)    NOT NULL,
    category_slug   VARCHAR(100)    NOT NULL UNIQUE,
    parent_id       INT UNSIGNED    NULL,
    description     TEXT            NULL,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    updated_at      DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    INDEX idx_parent_id (parent_id),
    CONSTRAINT fk_prs_categories_parent FOREIGN KEY (parent_id) REFERENCES prs_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "  prs_categories ✓\n";

// ── Step 5: Seed prs_categories ──
echo "\nSeeding prs_categories...\n";

// Parent categories (no parent_id)
$parents = [
    ['Special Education',           'special-education',           'Special education services, IEP implementation, evaluation, placement, and procedural compliance'],
    ['Civil Rights',                'civil-rights',                'Discrimination, harassment, bullying, and equal access complaints'],
    ['English Language Learners',   'english-language-learners',   'ELL/ESL program requirements and language access'],
    ['Title I',                     'title-i',                     'Title I federal program compliance'],
    ['Career / Vocational Technical', 'career-voc-tech',           'Career and vocational technical education programs'],
    ['Section 504',                 'section-504',                 'Section 504 accommodation plans and compliance'],
    ['General Education',           'general-education',           'General education matters including discipline, transportation, and licensure'],
    ['Restraint / Time Out',        'restraint-time-out',          'Physical restraint and time-out procedures and reporting'],
    ['Student Records',             'student-records',             'Student records access, privacy, and confidentiality'],
    ['Other',                       'other',                       'Miscellaneous and uncategorized complaints'],
];

$parentIds = [];
foreach ($parents as $p) {
    $existing = Database::fetch("SELECT id FROM prs_categories WHERE category_slug = ?", [$p[1]]);
    if ($existing) {
        $parentIds[$p[1]] = $existing['id'];
        echo "  (skip) {$p[0]}\n";
    } else {
        $id = Database::insert(
            'INSERT INTO prs_categories (category_name, category_slug, description) VALUES (?, ?, ?)',
            [$p[0], $p[1], $p[2]]
        );
        $parentIds[$p[1]] = $id;
        echo "  {$p[0]} (id={$id})\n";
    }
}

// Subcategories
$subcategories = [
    // Special Education
    ['IEP Implementation',  'iep-implementation',  'special-education',     'Implementation of Individualized Education Programs'],
    ['SE Procedural',       'se-procedural',       'special-education',     'Special education procedural compliance and timelines'],
    ['Evaluation',          'se-evaluation',       'special-education',     'Special education evaluation and eligibility determinations'],
    ['Placement',           'se-placement',        'special-education',     'Special education placement and least restrictive environment'],
    ['SE Discipline',       'se-discipline',       'special-education',     'Discipline of students with disabilities'],
    ['Related Services',    'related-services',    'special-education',     'Related services: speech, OT, PT, counseling, transportation'],
    ['Approved SE Schools', 'approved-se-schools', 'special-education',     'Approved special education schools and placements'],
    // Civil Rights
    ['Discrimination',  'discrimination',   'civil-rights', 'Discrimination based on protected class'],
    ['Harassment',      'harassment',       'civil-rights', 'Harassment including sexual, racial, and disability-based'],
    ['Bullying',        'bullying',         'civil-rights', 'Bullying prevention and response'],
    ['Equal Access',    'equal-access',     'civil-rights', 'Equal access to programs, activities, and facilities'],
    // English Language Learners
    ['ELL Program Requirements', 'ell-program',     'english-language-learners', 'ELL program identification, services, and staffing'],
    ['Language Access',          'language-access', 'english-language-learners', 'Language access for families and communications'],
    // General Education
    ['Gen Ed Discipline',    'gen-ed-discipline',     'general-education', 'General education discipline, suspension, and expulsion'],
    ['Transportation',       'gen-ed-transportation', 'general-education', 'General education transportation'],
    ['Licensure',            'gen-ed-licensure',      'general-education', 'Educator licensure and certification'],
    ['Diploma / Graduation', 'diploma-graduation',    'general-education', 'Diploma and graduation requirements'],
    ['Learning Time',        'learning-time',         'general-education', 'Learning time requirements'],
    // Restraint
    ['Restraint Procedures', 'restraint-procedures', 'restraint-time-out', 'Physical restraint procedures compliance'],
    ['Time Out Reporting',   'time-out-reporting',   'restraint-time-out', 'Time-out procedures and reporting requirements'],
    // Student Records
    ['Privacy / Confidentiality', 'privacy-confidentiality', 'student-records', 'Student data privacy and confidentiality'],
    ['Provision of Records',     'provision-of-records',    'student-records', 'Provision and access to student records'],
    // Other
    ['McKinney-Vento',          'mckinney-vento',       'other', 'McKinney-Vento Homeless Assistance Act'],
    ['METCO',                   'metco',                'other', 'METCO program'],
    ['Home / Hospital',         'home-hospital',        'other', 'Home and hospital tutoring'],
    ['Charter School',          'charter-school',       'other', 'Charter school procedures'],
    ['Nutrition',               'nutrition',            'other', 'School nutrition programs'],
    ['School Facilities',       'school-facilities',    'other', 'School facilities and safety'],
    ['Residency / Enrollment',  'residency-enrollment', 'other', 'Residency, admissions, and enrollment'],
    ['Student Fees',            'student-fees',         'other', 'Student fees and charges'],
];

foreach ($subcategories as $s) {
    $pId = $parentIds[$s[2]];
    $existing = Database::fetch("SELECT id FROM prs_categories WHERE category_slug = ?", [$s[1]]);
    if ($existing) {
        echo "  (skip) {$s[0]}\n";
    } else {
        Database::insert(
            'INSERT INTO prs_categories (category_name, category_slug, parent_id, description) VALUES (?, ?, ?, ?)',
            [$s[0], $s[1], $pId, $s[3]]
        );
        echo "  {$s[0]} (parent={$pId})\n";
    }
}

// ── Step 6: Migrate data from prs_intakes to prs_cases ──
echo "\nMigrating prs_intakes → prs_cases...\n";

$migrated = Database::execute("
INSERT INTO prs_cases (prs_number, org_id, case_title, filing_date, findings_issued_date, closure_date,
    current_status, resolution_type, allegations, created_at)
SELECT
    prs_number,
    org_id,
    CONCAT('PRS Case ', COALESCE(prs_number, 'Unknown')) as case_title,
    intake_date as filing_date,
    findings_date as findings_issued_date,
    CASE WHEN status = 'Closed' THEN findings_date ELSE NULL END as closure_date,
    CASE
        WHEN status = 'Intake Form Received/Uploaded' THEN 'filed'
        WHEN status IN ('CAP Received', 'CAP Requested', 'Progress Report Received') THEN 'investigating'
        WHEN status = 'Letter of Finding Issued' THEN 'findings'
        WHEN status = 'Closed' THEN 'closed'
        ELSE 'filed'
    END as current_status,
    CASE
        WHEN closure_code = 'Compliance found'              THEN 'substantiated'
        WHEN closure_code = 'No finding: Conflicting or inc' THEN 'unsubstantiated'
        WHEN closure_code IN ('No complainant''s cooperation', 'No formal complaint made',
                              'Closed: No Jurisdiction/moot', 'Closed: Other reason') THEN 'dismissed'
        WHEN closure_code = 'Prev. noncompliance remedied'  THEN 'resolved'
        WHEN closure_code = 'Closed: Complainant''s request' THEN 'withdrawn'
        WHEN closure_code = 'Case Inactive: Pending BSEA'    THEN 'resolved'
        ELSE NULL
    END as resolution_type,
    JSON_OBJECT('category', category, 'subcategory', subcategory) as allegations,
    NOW()
FROM prs_intakes
WHERE prs_number IS NOT NULL
");
echo "  Cases migrated: {$migrated}\n";

// ── Step 7: Create PRS events ──
echo "\nCreating PRS events...\n";

$filedCount = Database::execute("
INSERT INTO prs_events (prs_case_id, event_date, event_type, event_description, created_at)
SELECT
    c.id,
    i.intake_date,
    'filed',
    CONCAT('Case filed: ', COALESCE(i.category, 'Unknown category')),
    NOW()
FROM prs_cases c
JOIN prs_intakes i ON c.prs_number = i.prs_number
WHERE i.intake_date IS NOT NULL
");
echo "  Filed events: {$filedCount}\n";

$findingsCount = Database::execute("
INSERT INTO prs_events (prs_case_id, event_date, event_type, event_description, created_at)
SELECT
    c.id,
    i.findings_date,
    'findings_issued',
    CASE
        WHEN i.closure_code IS NOT NULL
        THEN CONCAT('Letter of Finding issued. Outcome: ', i.closure_code)
        ELSE 'Letter of Finding issued'
    END,
    NOW()
FROM prs_cases c
JOIN prs_intakes i ON c.prs_number = i.prs_number
WHERE i.findings_date IS NOT NULL
");
echo "  Findings events: {$findingsCount}\n";

$closedCount = Database::execute("
INSERT INTO prs_events (prs_case_id, event_date, event_type, event_description, created_at)
SELECT
    c.id,
    i.findings_date,
    'closed',
    CONCAT('Case closed. Resolution: ', COALESCE(i.closure_code, 'Not specified')),
    NOW()
FROM prs_cases c
JOIN prs_intakes i ON c.prs_number = i.prs_number
WHERE i.status = 'Closed' AND i.findings_date IS NOT NULL
");
echo "  Closed events: {$closedCount}\n";

// ── Step 8: Create PRS findings ──
echo "\nCreating PRS findings...\n";

$findingCount = Database::execute("
INSERT INTO prs_findings (prs_case_id, finding_number, allegation_category, allegation_subcategory, finding, finding_detail, created_at)
SELECT
    c.id,
    1 as finding_number,
    i.category as allegation_category,
    i.subcategory as allegation_subcategory,
    CASE
        WHEN i.closure_code = 'Compliance found'              THEN 'substantiated'
        WHEN i.closure_code = 'No finding: Conflicting or inc' THEN 'unsubstantiated'
        WHEN i.closure_code = 'Prev. noncompliance remedied'   THEN 'partially_substantiated'
        WHEN i.closure_code IN ('No complainant''s cooperation', 'No formal complaint made',
                                'Closed: No Jurisdiction/moot', 'Closed: Other reason',
                                'Closed: Complainant''s request', 'Case Inactive: Pending BSEA') THEN NULL
        ELSE NULL
    END as finding,
    CASE
        WHEN i.closure_code IS NOT NULL
        THEN CONCAT('PRS intake closure code: ', i.closure_code)
        ELSE NULL
    END as finding_detail,
    NOW()
FROM prs_cases c
JOIN prs_intakes i ON c.prs_number = i.prs_number
WHERE i.category IS NOT NULL OR i.subcategory IS NOT NULL
");
echo "  Findings created: {$findingCount}\n";

// ── Step 9: Verification ──
echo "\n=== Verification ===\n";

$caseCount = Database::fetchColumn("SELECT COUNT(*) FROM prs_cases");
echo "prs_cases total rows: {$caseCount}\n";

$eventCount = Database::fetchColumn("SELECT COUNT(*) FROM prs_events");
echo "prs_events total rows: {$eventCount}\n";

$findingCount2 = Database::fetchColumn("SELECT COUNT(*) FROM prs_findings");
echo "prs_findings total rows: {$findingCount2}\n";

$catCount = Database::fetchColumn("SELECT COUNT(*) FROM prs_categories");
echo "prs_categories total rows: {$catCount}\n";

echo "\nStatus distribution:\n";
$statusRows = Database::fetchAll("SELECT current_status, COUNT(*) as cnt FROM prs_cases GROUP BY current_status ORDER BY cnt DESC");
foreach ($statusRows as $row) {
    echo "  {$row['current_status']}: {$row['cnt']}\n";
}

echo "\nResolution type distribution:\n";
$resRows = Database::fetchAll("SELECT resolution_type, COUNT(*) as cnt FROM prs_cases GROUP BY resolution_type ORDER BY cnt DESC");
foreach ($resRows as $row) {
    $r = $row['resolution_type'] ?? 'NULL';
    echo "  {$r}: {$row['cnt']}\n";
}

echo "\nEvent type distribution:\n";
$eventRows = Database::fetchAll("SELECT event_type, COUNT(*) as cnt FROM prs_events GROUP BY event_type ORDER BY cnt DESC");
foreach ($eventRows as $row) {
    echo "  {$row['event_type']}: {$row['cnt']}\n";
}

echo "\n=== Migration complete ===\n";
