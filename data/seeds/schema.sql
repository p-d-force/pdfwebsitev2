-- ============================================================
-- PDFWEBSITE Unified Schema — MariaDB 10.11
-- Redesigned 2026-07-14 from DESE Organization Search exports
-- ============================================================
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- Core: Unified organizations table (replaces districts)
-- ============================================================

CREATE TABLE organizations (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    org_code        VARCHAR(10)     NOT NULL UNIQUE,
    org_name        VARCHAR(512)    NOT NULL,
    org_type        VARCHAR(100)    NULL,
    parent_org_code VARCHAR(10)     NULL,
    function_area   VARCHAR(100)    NULL,
    contact_name    VARCHAR(255)    NULL,
    address_1       VARCHAR(255)    NULL,
    address_2       VARCHAR(255)    NULL,
    town            VARCHAR(100)    NULL,
    state           CHAR(2)         NULL DEFAULT 'MA',
    zip             VARCHAR(10)     NULL,
    phone           VARCHAR(20)     NULL,
    fax             VARCHAR(20)     NULL,
    grade_span      VARCHAR(50)     NULL,
    title_1_status  VARCHAR(50)     NULL,
    tags            JSON            NULL,
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    updated_at      DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    INDEX idx_org_code (org_code),
    INDEX idx_org_type (org_type),
    INDEX idx_parent_org_code (parent_org_code),
    INDEX idx_town (town),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Content Tables
-- ============================================================

CREATE TABLE cases (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    case_number     VARCHAR(100)    NOT NULL UNIQUE,
    title           VARCHAR(512)    NOT NULL,
    slug            VARCHAR(512)    NOT NULL UNIQUE,
    case_type       VARCHAR(100)    NULL,
    status          VARCHAR(50)     NULL,
    filed_date      DATE            NULL,
    resolved_date   DATE            NULL,
    court           VARCHAR(255)    NULL,
    judge           VARCHAR(255)    NULL,
    plaintiff       VARCHAR(512)    NULL,
    defendant       VARCHAR(512)    NULL,
    district_id     INT UNSIGNED    NULL,
    summary         TEXT            NULL,
    body            LONGTEXT        NULL,
    ruling          TEXT            NULL,
    settlement      TEXT            NULL,
    external_url    VARCHAR(1024)   NULL,
    docket_url      VARCHAR(1024)   NULL,
    is_featured     TINYINT(1)      NOT NULL DEFAULT 0,
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    sort_order      INT             NOT NULL DEFAULT 0,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    updated_at      DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    INDEX idx_case_number (case_number),
    INDEX idx_district_id (district_id),
    INDEX idx_case_type (case_type),
    INDEX idx_status (status),
    INDEX idx_filed_date (filed_date),
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE case_documents (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    case_id         INT UNSIGNED    NOT NULL,
    document_name   VARCHAR(512)    NOT NULL,
    document_type   VARCHAR(100)    NULL,
    file_url        VARCHAR(1024)   NOT NULL,
    file_size       INT UNSIGNED    NULL,
    mime_type       VARCHAR(100)    NULL,
    sort_order      INT             NOT NULL DEFAULT 0,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    updated_at      DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    INDEX idx_case_id (case_id),
    INDEX idx_document_type (document_type),
    CONSTRAINT fk_case_documents_case FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE articles (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(512)    NOT NULL,
    slug            VARCHAR(512)    NOT NULL UNIQUE,
    subtitle        VARCHAR(512)    NULL,
    author          VARCHAR(255)    NULL,
    source_name     VARCHAR(255)    NULL,
    source_url      VARCHAR(1024)   NULL,
    excerpt         TEXT            NULL,
    body            LONGTEXT        NULL,
    image_url       VARCHAR(1024)   NULL,
    thumbnail_url   VARCHAR(1024)   NULL,
    published_date  DATE            NULL,
    article_type    VARCHAR(50)     NULL,
    is_featured     TINYINT(1)      NOT NULL DEFAULT 0,
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    sort_order      INT             NOT NULL DEFAULT 0,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    updated_at      DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    INDEX idx_slug (slug),
    INDEX idx_article_type (article_type),
    INDEX idx_published_date (published_date),
    INDEX idx_is_featured (is_featured),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE article_case_links (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    article_id      INT UNSIGNED    NOT NULL,
    case_id         INT UNSIGNED    NOT NULL,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    UNIQUE KEY uq_article_case (article_id, case_id),
    INDEX idx_article_id (article_id),
    INDEX idx_case_id (case_id),
    CONSTRAINT fk_acl_article FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    CONSTRAINT fk_acl_case FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE article_org_links (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    article_id      INT UNSIGNED    NOT NULL,
    org_id          INT UNSIGNED    NOT NULL,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    UNIQUE KEY uq_article_org (article_id, org_id),
    INDEX idx_article_id (article_id),
    INDEX idx_org_id (org_id),
    CONSTRAINT fk_aol_article FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    CONSTRAINT fk_aol_org FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE article_tags (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tag_name        VARCHAR(100)    NOT NULL UNIQUE,
    slug            VARCHAR(100)    NOT NULL UNIQUE,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    updated_at      DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    INDEX idx_tag_name (tag_name),
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE article_tag_links (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    article_id      INT UNSIGNED    NOT NULL,
    tag_id          INT UNSIGNED    NOT NULL,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    UNIQUE KEY uq_article_tag (article_id, tag_id),
    INDEX idx_article_id (article_id),
    INDEX idx_tag_id (tag_id),
    CONSTRAINT fk_atl_article FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    CONSTRAINT fk_atl_tag FOREIGN KEY (tag_id) REFERENCES article_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE speeches (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(512)    NOT NULL,
    slug            VARCHAR(512)    NOT NULL UNIQUE,
    speaker_name    VARCHAR(255)    NULL,
    speaker_title   VARCHAR(255)    NULL,
    event_name      VARCHAR(255)    NULL,
    event_date      DATE            NULL,
    venue           VARCHAR(255)    NULL,
    video_url       VARCHAR(1024)   NULL,
    transcript      LONGTEXT        NULL,
    excerpt         TEXT            NULL,
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    sort_order      INT             NOT NULL DEFAULT 0,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    updated_at      DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    INDEX idx_slug (slug),
    INDEX idx_event_date (event_date),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE updates (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(512)    NOT NULL,
    slug            VARCHAR(512)    NOT NULL UNIQUE,
    update_type     VARCHAR(50)     NULL,
    body            LONGTEXT        NULL,
    excerpt         TEXT            NULL,
    source_url      VARCHAR(1024)   NULL,
    published_date  DATETIME        NULL,
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    is_featured     TINYINT(1)      NOT NULL DEFAULT 0,
    sort_order      INT             NOT NULL DEFAULT 0,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    updated_at      DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    INDEX idx_slug (slug),
    INDEX idx_update_type (update_type),
    INDEX idx_published_date (published_date),
    INDEX idx_is_active (is_active),
    INDEX idx_is_featured (is_featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE submissions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    submitter_name  VARCHAR(255)    NULL,
    submitter_email VARCHAR(255)    NULL,
    submitter_org   VARCHAR(255)    NULL,
    title           VARCHAR(512)    NOT NULL,
    body            LONGTEXT        NULL,
    file_url        VARCHAR(1024)   NULL,
    submission_type VARCHAR(50)     NULL,
    status          VARCHAR(50)     NOT NULL DEFAULT 'pending',
    reviewed_by     INT UNSIGNED    NULL,
    reviewed_at     DATETIME        NULL,
    notes           TEXT            NULL,
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    submitted_at    DATETIME        NOT NULL DEFAULT NOW(),
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    updated_at      DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    INDEX idx_status (status),
    INDEX idx_submission_type (submission_type),
    INDEX idx_submitted_at (submitted_at),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE resources (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(512)    NOT NULL,
    slug            VARCHAR(512)    NOT NULL UNIQUE,
    resource_type   VARCHAR(50)     NULL,
    description     TEXT            NULL,
    body            LONGTEXT        NULL,
    file_url        VARCHAR(1024)   NULL,
    external_url    VARCHAR(1024)   NULL,
    author          VARCHAR(255)    NULL,
    published_date  DATE            NULL,
    is_featured     TINYINT(1)      NOT NULL DEFAULT 0,
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    sort_order      INT             NOT NULL DEFAULT 0,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    updated_at      DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    INDEX idx_slug (slug),
    INDEX idx_resource_type (resource_type),
    INDEX idx_is_featured (is_featured),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE media_appearances (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(512)    NULL,
    appearance_date DATE            NULL,
    venue           VARCHAR(255)    NULL,
    url             VARCHAR(1024)   NULL,
    description     TEXT            NULL,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    updated_at      DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    INDEX idx_appearance_date (appearance_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DESE Data Tables
-- ============================================================

CREATE TABLE restraint_data (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    org_code            VARCHAR(10)     NOT NULL,
    school_year         VARCHAR(10)     NOT NULL,
    enrollment          INT             NULL,
    students_restrained INT             NULL,
    total_restraints    INT             NULL,
    total_injuries      INT             NULL,
    restraint_rate_per_100  DECIMAL(8,2) NULL,
    injuries_per_restraint  DECIMAL(8,4) NULL,
    created_at          DATETIME        NOT NULL DEFAULT NOW(),
    updated_at          DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    UNIQUE KEY uq_restraint_org_yr (org_code, school_year),
    INDEX idx_org_code (org_code),
    INDEX idx_school_year (school_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE discipline_data (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    district_name           VARCHAR(255)    NOT NULL,
    district_code           VARCHAR(10)     NOT NULL,
    school_year             VARCHAR(10)     NOT NULL,
    students                INT             NULL,
    students_disciplined    INT             NULL,
    pct_in_school_susp      DECIMAL(5,2)    NULL,
    pct_out_school_susp     DECIMAL(5,2)    NULL,
    pct_expulsion           DECIMAL(5,2)    NULL,
    pct_alt_setting         DECIMAL(5,2)    NULL,
    pct_emergency_removal   DECIMAL(5,2)    NULL,
    pct_arrest              DECIMAL(5,2)    NULL,
    pct_law_enforce         DECIMAL(5,2)    NULL,
    created_at              DATETIME        NOT NULL DEFAULT NOW(),
    updated_at              DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    UNIQUE KEY uq_discipline_district_yr (district_code, school_year),
    INDEX idx_district_name (district_name),
    INDEX idx_district_code (district_code),
    INDEX idx_school_year (school_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE enrollment_data (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    district_name       VARCHAR(255)    NOT NULL,
    district_code       VARCHAR(10)     NOT NULL,
    school_year         VARCHAR(10)     NOT NULL,
    high_needs_num      INT             NULL,
    high_needs_pct      DECIMAL(5,1)    NULL,
    el_num              INT             NULL,
    el_pct              DECIMAL(5,1)    NULL,
    flne_num            INT             NULL,
    flne_pct            DECIMAL(5,1)    NULL,
    low_income_num      INT             NULL,
    low_income_pct      DECIMAL(5,1)    NULL,
    sped_num            INT             NULL,
    sped_pct            DECIMAL(5,1)    NULL,
    created_at          DATETIME        NOT NULL DEFAULT NOW(),
    updated_at          DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    UNIQUE KEY uq_enrollment_district_yr (district_code, school_year),
    INDEX idx_district_name (district_name),
    INDEX idx_district_code (district_code),
    INDEX idx_school_year (school_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sped_results (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    district_name           VARCHAR(255)    NOT NULL,
    district_code           VARCHAR(10)     NOT NULL,
    school_year             VARCHAR(10)     NOT NULL,
    sped_grad_rate          DECIMAL(5,1)    NULL,
    sped_dropout_rate       DECIMAL(5,1)    NULL,
    lre_full_incl_pct       DECIMAL(5,1)    NULL,
    parent_involve_pct      DECIMAL(5,1)    NULL,
    post_school_engage_pct  DECIMAL(5,1)    NULL,
    created_at              DATETIME        NOT NULL DEFAULT NOW(),
    updated_at              DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    UNIQUE KEY uq_sped_results_district_yr (district_code, school_year),
    INDEX idx_district_name (district_name),
    INDEX idx_district_code (district_code),
    INDEX idx_school_year (school_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE attendance_data (
    id                          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    district_name               VARCHAR(255)    NOT NULL,
    district_code               VARCHAR(10)     NOT NULL,
    school_year                 VARCHAR(10)     NOT NULL,
    attendance_rate             DECIMAL(5,1)    NULL,
    avg_absences                DECIMAL(5,1)    NULL,
    absent_10_plus_pct          DECIMAL(5,1)    NULL,
    chronically_absent_10_pct   DECIMAL(5,1)    NULL,
    chronically_absent_20_pct   DECIMAL(5,1)    NULL,
    created_at                  DATETIME        NOT NULL DEFAULT NOW(),
    updated_at                  DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    UNIQUE KEY uq_attendance_district_yr (district_code, school_year),
    INDEX idx_district_name (district_name),
    INDEX idx_district_code (district_code),
    INDEX idx_school_year (school_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE prs_data (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    district_name           VARCHAR(255)    NOT NULL,
    district_code           VARCHAR(10)     NOT NULL,
    school_year             VARCHAR(10)     NOT NULL,
    prs_level               VARCHAR(50)     NULL,
    prs_rating              VARCHAR(50)     NULL,
    indicator_1_score       DECIMAL(5,1)    NULL,
    indicator_2_score       DECIMAL(5,1)    NULL,
    indicator_3_score       DECIMAL(5,1)    NULL,
    indicator_4_score       DECIMAL(5,1)    NULL,
    indicator_5_score       DECIMAL(5,1)    NULL,
    indicator_6_score       DECIMAL(5,1)    NULL,
    indicator_7_score       DECIMAL(5,1)    NULL,
    criterion_1_score       DECIMAL(5,1)    NULL,
    criterion_2_score       DECIMAL(5,1)    NULL,
    created_at              DATETIME        NOT NULL DEFAULT NOW(),
    updated_at              DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    UNIQUE KEY uq_prs_district_yr (district_code, school_year),
    INDEX idx_district_name (district_name),
    INDEX idx_district_code (district_code),
    INDEX idx_school_year (school_year),
    INDEX idx_prs_level (prs_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE prs_intakes_data (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prs_number          VARCHAR(50)     NULL,
    district_agency     VARCHAR(255)    NULL,
    intake_date         DATE            NULL,
    status              VARCHAR(50)     NULL,
    findings_date       DATE            NULL,
    category            VARCHAR(100)    NULL,
    subcategory         VARCHAR(100)    NULL,
    closure_code        VARCHAR(50)     NULL,
    created_at          DATETIME        NOT NULL DEFAULT NOW(),
    updated_at          DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    INDEX idx_prs_number (prs_number),
    INDEX idx_district_agency (district_agency),
    INDEX idx_status (status),
    INDEX idx_intake_date (intake_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sped_complaints (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    complaint_number    VARCHAR(50)     NULL,
    district_agency     VARCHAR(255)    NULL,
    intake_date         DATE            NULL,
    status              VARCHAR(50)     NULL,
    findings_date       DATE            NULL,
    category            VARCHAR(100)    NULL,
    subcategory         VARCHAR(100)    NULL,
    closure_code        VARCHAR(50)     NULL,
    created_at          DATETIME        NOT NULL DEFAULT NOW(),
    updated_at          DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    INDEX idx_complaint_number (complaint_number),
    INDEX idx_district_agency (district_agency),
    INDEX idx_status (status),
    INDEX idx_intake_date (intake_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE aggregate_catalog (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dataset_name        VARCHAR(100)    NOT NULL,
    dataset_slug        VARCHAR(100)    NOT NULL UNIQUE,
    source_agency       VARCHAR(255)    NULL,
    source_url          TEXT            NULL,
    source_description  TEXT            NULL,
    source_last_updated VARCHAR(100)    NULL,
    update_frequency    VARCHAR(50)     NULL,
    data_format         VARCHAR(50)     NULL,
    file_url            VARCHAR(1024)   NULL,
    field_count         INT             NULL,
    record_count        INT             NULL,
    notes               TEXT            NULL,
    is_active           TINYINT(1)      NOT NULL DEFAULT 1,
    created_at          DATETIME        NOT NULL DEFAULT NOW(),
    updated_at          DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    INDEX idx_dataset_name (dataset_name),
    INDEX idx_dataset_slug (dataset_slug),
    INDEX idx_source_agency (source_agency),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE prr_tracker (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_name            VARCHAR(512)    NULL,
    matter_type             VARCHAR(100)    NULL,
    agency                  VARCHAR(255)    NULL,
    stage                   VARCHAR(50)     NULL,
    request_date            DATE            NULL,
    last_activity           DATE            NULL,
    deadline_regime         VARCHAR(255)    NULL,
    initial_response_due    DATE            NULL,
    initial_response_date   DATE            NULL,
    initial_response_timely VARCHAR(50)     NULL,
    standard_access_due     DATE            NULL,
    stated_agreed_due       DATE            NULL,
    deadline_basis          TEXT            NULL,
    current_deadline_status VARCHAR(100)    NULL,
    request_summary         TEXT            NULL,
    timeframe_scope         TEXT            NULL,
    custodian_scope         TEXT            NULL,
    record_category_scope   TEXT            NULL,
    search_terms            TEXT            NULL,
    exclusions              TEXT            NULL,
    notes                   TEXT            NULL,
    created_at              DATETIME        NOT NULL DEFAULT NOW(),
    updated_at              DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    INDEX idx_agency (agency),
    INDEX idx_stage (stage),
    INDEX idx_request_date (request_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Admin / Auth Tables
-- ============================================================

CREATE TABLE admin_users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(100)    NOT NULL UNIQUE,
    email           VARCHAR(255)    NOT NULL UNIQUE,
    display_name    VARCHAR(255)    NULL,
    password_hash   VARCHAR(255)    NOT NULL,
    role            VARCHAR(50)     NOT NULL DEFAULT 'editor',
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    last_login_at   DATETIME        NULL,
    last_login_ip   VARCHAR(45)     NULL,
    reset_token     VARCHAR(255)    NULL,
    reset_expires   DATETIME        NULL,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    updated_at      DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    UNIQUE KEY uq_admin_username (username),
    UNIQUE KEY uq_admin_email (email),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_sessions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_user_id   INT UNSIGNED    NOT NULL,
    session_token   VARCHAR(255)    NOT NULL UNIQUE,
    ip_address      VARCHAR(45)     NULL,
    user_agent      VARCHAR(512)    NULL,
    expires_at      DATETIME        NOT NULL,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    INDEX idx_session_token (session_token),
    INDEX idx_admin_user_id (admin_user_id),
    INDEX idx_expires_at (expires_at),
    CONSTRAINT fk_admin_sessions_user FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_log (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_user_id   INT UNSIGNED    NULL,
    action          VARCHAR(100)    NOT NULL,
    entity_type     VARCHAR(100)    NULL,
    entity_id       INT UNSIGNED    NULL,
    old_values      JSON            NULL,
    new_values      JSON            NULL,
    ip_address      VARCHAR(45)     NULL,
    user_agent      VARCHAR(512)    NULL,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    INDEX idx_admin_user_id (admin_user_id),
    INDEX idx_action (action),
    INDEX idx_entity_type (entity_type),
    INDEX idx_entity_id (entity_id),
    INDEX idx_created_at (created_at),
    CONSTRAINT fk_audit_log_user FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE system_config (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    config_key      VARCHAR(100)    NOT NULL UNIQUE,
    config_value    TEXT            NULL,
    config_type     VARCHAR(50)     NOT NULL DEFAULT 'string',
    description     VARCHAR(512)    NULL,
    is_public       TINYINT(1)      NOT NULL DEFAULT 0,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    updated_at      DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    UNIQUE KEY uq_config_key (config_key),
    INDEX idx_is_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sync_log (
    dataset             VARCHAR(50)     PRIMARY KEY,
    source_url          TEXT            NULL,
    last_synced         DATETIME        NULL,
    row_count           INT             NULL,
    source_last_updated VARCHAR(100)    NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Seed Data
-- ============================================================

INSERT IGNORE INTO admin_users (id, username, email, display_name, password_hash, role, is_active) VALUES
(1, 'admin', 'admin@pdfwebsite.local', 'Site Administrator', '$2y$10$eKKCt1I4.NtxNJFGp73GCe.sgb5afmD0gnGn6w4Cg8JObarglryju', 'superadmin', 1);

INSERT IGNORE INTO system_config (config_key, config_value, config_type, description, is_public) VALUES
('site_name',              'PDF Website',           'string',  'Public-facing site name',                    1),
('site_tagline',           'Massachusetts Education Data & Oversight', 'string', 'Site tagline / subtitle', 1),
('site_url',               'https://pdfwebsite.local', 'string', 'Canonical site URL (no trailing slash)',    0),
('admin_email',            'admin@pdfwebsite.local', 'string',   'Contact email for site notifications',      0),
('records_per_page',       '25',                    'integer',  'Default pagination size for data tables',     0),
('default_timeline_years', '3',                     'integer',  'Default number of years shown on dashboards', 0),
('maintenance_mode',       '0',                     'boolean',  'Enable maintenance mode (1 = on)',            0),
('google_analytics_id',    '',                      'string',   'Google Analytics measurement ID',             0),
('meta_description',       'Massachusetts education oversight and data transparency platform.', 'string', 'Default meta description for SEO', 1);


-- ============================================================
-- Data: Teacher salaries by district
-- ============================================================

CREATE TABLE teacher_salaries (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    district_code   VARCHAR(10)     NOT NULL,
    district_name   VARCHAR(255)    NOT NULL,
    school_year     VARCHAR(10)     NOT NULL,
    salary_totals   DECIMAL(14,2)   NULL,
    average_salary  DECIMAL(10,2)   NULL,
    fte_count       DECIMAL(8,1)    NULL,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    updated_at      DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    UNIQUE KEY uq_teacher_salaries_district_yr (district_code, school_year),
    INDEX idx_district_code (district_code),
    INDEX idx_school_year (school_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Unified Documents & Exhibits
-- ============================================================

CREATE TABLE documents (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    -- Classification
    doc_type            VARCHAR(50)     NOT NULL,
    doc_subtype         VARCHAR(100)    NULL,
    -- Content
    title               VARCHAR(512)    NOT NULL,
    description         TEXT            NULL,
    body_text           LONGTEXT        NULL,
    -- File
    file_path           VARCHAR(1024)   NULL,
    file_url            VARCHAR(1024)   NULL,
    file_name           VARCHAR(512)    NULL,
    file_size           INT UNSIGNED    NULL,
    mime_type           VARCHAR(100)    NULL,
    file_hash           VARCHAR(64)     NULL,
    -- Metadata
    source              VARCHAR(255)    NULL,
    source_email_id     VARCHAR(255)    NULL,
    received_date       DATETIME        NULL,
    sent_date           DATETIME        NULL,
    authored_date       DATE            NULL,
    -- People
    author_name         VARCHAR(255)    NULL,
    author_org          VARCHAR(255)    NULL,
    recipient_name      VARCHAR(255)    NULL,
    recipient_org       VARCHAR(255)    NULL,
    agent_assigned      VARCHAR(255)    NULL,
    -- Decisions
    decision_summary    TEXT            NULL,
    decision_date       DATE            NULL,
    decision_by         VARCHAR(255)    NULL,
    -- Processing
    needs_ocr           TINYINT(1)      NOT NULL DEFAULT 0,
    ocr_status          VARCHAR(50)     NULL,
    is_readable         TINYINT(1)      NOT NULL DEFAULT 0,
    page_count          INT             NULL,
    language            VARCHAR(10)     NULL DEFAULT 'en',
    -- Links
    parent_doc_id       INT UNSIGNED    NULL,
    case_id             INT UNSIGNED    NULL,
    org_id              INT UNSIGNED    NULL,
    prs_number          VARCHAR(50)     NULL,
    prr_request_id      INT UNSIGNED    NULL,
    -- Status
    is_public           TINYINT(1)      NOT NULL DEFAULT 0,
    is_active           TINYINT(1)      NOT NULL DEFAULT 1,
    sort_order          INT             NOT NULL DEFAULT 0,
    created_at          DATETIME        NOT NULL DEFAULT NOW(),
    updated_at          DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    -- Indexes
    INDEX idx_doc_type (doc_type),
    INDEX idx_doc_subtype (doc_subtype),
    INDEX idx_received_date (received_date),
    INDEX idx_file_hash (file_hash),
    INDEX idx_case_id (case_id),
    INDEX idx_org_id (org_id),
    INDEX idx_prs_number (prs_number),
    INDEX idx_prr_request_id (prr_request_id),
    INDEX idx_needs_ocr (needs_ocr),
    INDEX idx_is_readable (is_readable),
    INDEX idx_parent_doc_id (parent_doc_id),
    -- Foreign keys
    CONSTRAINT fk_documents_case FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE SET NULL,
    CONSTRAINT fk_documents_org FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE document_tags (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    doc_id      INT UNSIGNED    NOT NULL,
    tag         VARCHAR(100)    NOT NULL,
    created_at  DATETIME        NOT NULL DEFAULT NOW(),
    UNIQUE KEY uq_doc_tag (doc_id, tag),
    INDEX idx_tag (tag),
    CONSTRAINT fk_doctag_doc FOREIGN KEY (doc_id) REFERENCES documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
