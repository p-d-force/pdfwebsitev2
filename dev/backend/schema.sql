-- ============================================================================
-- Parent Data Force — Production Schema
-- MariaDB 10.11+ / InnoDB / utf8mb4
-- Designed 2026-07-14 from greenfield rebuild
--
-- Architecture decisions:
--   - organizations is the central hub — every table FKs to it via org_id INT
--   - NO string joins anywhere — all cross-table references are INT FKs
--   - documents split into 3 tables: documents + document_links + document_workflow
--   - PRS/PRR tables use org_id FK (name→code resolution happens at import time)
--   - admin tables: users, sessions (DB-backed), audit_log, system_config
-- ============================================================================
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- CENTRAL HUB: organizations
-- 17 org_types from DESE exports. Type-specific fields are nullable.
-- tags JSON stores type-specific metadata (e.g. charter authorizer, vocational programs).
-- ============================================================================
CREATE TABLE organizations (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    org_code        VARCHAR(10)     NOT NULL UNIQUE,
    org_name        VARCHAR(512)    NOT NULL,
    org_type        VARCHAR(100)    NOT NULL,
    parent_org_id   INT UNSIGNED    NULL COMMENT 'FK self — schools → their district',
    function_area   VARCHAR(100)    NULL COMMENT 'SPED program/service area',
    contact_name    VARCHAR(255)    NULL,
    address_1       VARCHAR(255)    NULL,
    town            VARCHAR(100)    NULL,
    state           CHAR(2)         NOT NULL DEFAULT 'MA',
    zip             VARCHAR(10)     NULL,
    phone           VARCHAR(20)     NULL,
    grade_span      VARCHAR(50)     NULL COMMENT 'e.g. PK-12, 09-12',
    title_1_status  VARCHAR(50)     NULL COMMENT 'Title 1 schoolwide, targeted, etc.',
    tags            JSON            NULL COMMENT 'Type-specific metadata from DESE exports',
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    updated_at      DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),

    INDEX idx_org_code (org_code),
    INDEX idx_org_type (org_type),
    INDEX idx_parent_org_id (parent_org_id),
    INDEX idx_town (town),
    INDEX idx_is_active (is_active),

    CONSTRAINT fk_org_parent
        FOREIGN KEY (parent_org_id) REFERENCES organizations(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- CONTENT: cases, articles, speeches, updates, submissions, resources
-- ============================================================================

CREATE TABLE cases (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    case_number     VARCHAR(100)    NOT NULL UNIQUE,
    title           VARCHAR(512)    NOT NULL,
    slug            VARCHAR(512)    NOT NULL UNIQUE,
    case_type       VARCHAR(100)    NULL COMMENT 'BSEA, SPR, PRS, PRR, OCR, court, etc.',
    status          VARCHAR(50)     NOT NULL DEFAULT 'open' COMMENT 'open, active, resolved, closed, appealed',
    filed_date      DATE            NULL,
    resolved_date   DATE            NULL,
    court           VARCHAR(255)    NULL,
    docket_number   VARCHAR(100)    NULL,
    plaintiff       VARCHAR(512)    NULL,
    defendant       VARCHAR(512)    NULL,
    org_id          INT UNSIGNED    NULL COMMENT 'Primary district/organization involved',
    summary         TEXT            NULL,
    body            LONGTEXT        NULL COMMENT 'Full case narrative',
    ruling          TEXT            NULL,
    external_url    VARCHAR(1024)   NULL,
    docket_url      VARCHAR(1024)   NULL,
    is_featured     TINYINT(1)      NOT NULL DEFAULT 0,
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    sort_order      INT             NOT NULL DEFAULT 0,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    updated_at      DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),

    INDEX idx_case_number (case_number),
    INDEX idx_slug (slug),
    INDEX idx_case_type (case_type),
    INDEX idx_status (status),
    INDEX idx_filed_date (filed_date),
    INDEX idx_org_id (org_id),
    INDEX idx_is_featured (is_featured),
    INDEX idx_is_active (is_active),

    FULLTEXT INDEX ft_cases_search (title, summary, body),

    CONSTRAINT fk_cases_org
        FOREIGN KEY (org_id) REFERENCES organizations(id)
        ON DELETE SET NULL
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
    article_type    VARCHAR(50)     NULL COMMENT 'analysis, news, guide, report, opinion',
    is_featured     TINYINT(1)      NOT NULL DEFAULT 0,
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    sort_order      INT             NOT NULL DEFAULT 0,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    updated_at      DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),

    INDEX idx_slug (slug),
    INDEX idx_article_type (article_type),
    INDEX idx_published_date (published_date),
    INDEX idx_is_featured (is_featured),
    INDEX idx_is_active (is_active),

    FULLTEXT INDEX ft_articles_search (title, excerpt, body)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Junction: articles ↔ cases
CREATE TABLE article_case_links (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    article_id      INT UNSIGNED    NOT NULL,
    case_id         INT UNSIGNED    NOT NULL,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),

    UNIQUE KEY uq_article_case (article_id, case_id),
    INDEX idx_article_id (article_id),
    INDEX idx_case_id (case_id),

    CONSTRAINT fk_acl_article
        FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    CONSTRAINT fk_acl_case
        FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Junction: articles ↔ organizations
CREATE TABLE article_org_links (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    article_id      INT UNSIGNED    NOT NULL,
    org_id          INT UNSIGNED    NOT NULL,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),

    UNIQUE KEY uq_article_org (article_id, org_id),
    INDEX idx_article_id (article_id),
    INDEX idx_org_id (org_id),

    CONSTRAINT fk_aol_article
        FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    CONSTRAINT fk_aol_org
        FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE
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

    CONSTRAINT fk_atl_article
        FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    CONSTRAINT fk_atl_tag
        FOREIGN KEY (tag_id) REFERENCES article_tags(id) ON DELETE CASCADE
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
    org_id          INT UNSIGNED    NULL COMMENT 'District this speech/testimony concerns',
    video_url       VARCHAR(1024)   NULL,
    transcript      LONGTEXT        NULL,
    excerpt         TEXT            NULL,
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    sort_order      INT             NOT NULL DEFAULT 0,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    updated_at      DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),

    INDEX idx_slug (slug),
    INDEX idx_event_date (event_date),
    INDEX idx_org_id (org_id),
    INDEX idx_is_active (is_active),

    CONSTRAINT fk_speeches_org
        FOREIGN KEY (org_id) REFERENCES organizations(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE updates (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(512)    NOT NULL,
    slug            VARCHAR(512)    NOT NULL UNIQUE,
    update_type     VARCHAR(50)     NULL COMMENT 'case, data, site, advocacy',
    body            LONGTEXT        NULL,
    excerpt         TEXT            NULL,
    source_url      VARCHAR(1024)   NULL,
    org_id          INT UNSIGNED    NULL COMMENT 'Related district, if applicable',
    case_id         INT UNSIGNED    NULL COMMENT 'Related case, if applicable',
    published_date  DATETIME        NULL,
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    is_featured     TINYINT(1)      NOT NULL DEFAULT 0,
    sort_order      INT             NOT NULL DEFAULT 0,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    updated_at      DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),

    INDEX idx_slug (slug),
    INDEX idx_update_type (update_type),
    INDEX idx_published_date (published_date),
    INDEX idx_org_id (org_id),
    INDEX idx_case_id (case_id),
    INDEX idx_is_active (is_active),
    INDEX idx_is_featured (is_featured),

    CONSTRAINT fk_updates_org
        FOREIGN KEY (org_id) REFERENCES organizations(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_updates_case
        FOREIGN KEY (case_id) REFERENCES cases(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE submissions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    submitter_name  VARCHAR(255)    NULL,
    submitter_email VARCHAR(255)    NULL,
    submitter_org   VARCHAR(255)    NULL,
    title           VARCHAR(512)    NOT NULL,
    body            LONGTEXT        NULL,
    file_url        VARCHAR(1024)   NULL,
    submission_type VARCHAR(50)     NULL COMMENT 'tip, help_request, upload, document',
    status          VARCHAR(50)     NOT NULL DEFAULT 'pending' COMMENT 'pending, reviewed, accepted, rejected',
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
    INDEX idx_is_active (is_active),

    CONSTRAINT fk_submissions_reviewer
        FOREIGN KEY (reviewed_by) REFERENCES admin_users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE resources (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(512)    NOT NULL,
    slug            VARCHAR(512)    NOT NULL UNIQUE,
    resource_type   VARCHAR(50)     NULL COMMENT 'guide, template, tool, link, document',
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
    venue           VARCHAR(255)    NULL COMMENT 'e.g. School Committee, NECN, Boston Globe',
    url             VARCHAR(1024)   NULL,
    description     TEXT            NULL,
    org_id          INT UNSIGNED    NULL COMMENT 'District this appearance concerns',
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    updated_at      DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),

    INDEX idx_appearance_date (appearance_date),
    INDEX idx_org_id (org_id),

    CONSTRAINT fk_media_org
        FOREIGN KEY (org_id) REFERENCES organizations(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- UNIFIED DOCUMENT SYSTEM
-- Split into 3 tables per docs/02 recommendation:
--   documents — file metadata + content
--   document_links — polymorphic relationships to cases, orgs, PRS, PRR
--   document_workflow — ingestion pipeline state
-- ============================================================================

CREATE TABLE documents (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    -- Classification
    doc_family      VARCHAR(50)     NULL COMMENT 'prs, prr, determination, correspondence, email, spreadsheet, attachment',
    doc_type        VARCHAR(100)    NULL,
    doc_subtype     VARCHAR(100)    NULL,
    -- Content
    title           VARCHAR(512)    NULL,
    description     TEXT            NULL,
    body_text       LONGTEXT        NULL COMMENT 'Extracted text content',
    -- File
    file_name       VARCHAR(512)    NULL,
    file_path       VARCHAR(1024)   NULL,
    file_url        VARCHAR(1024)   NULL,
    file_size       INT UNSIGNED    NULL,
    file_mime       VARCHAR(100)    NULL,
    file_hash       VARCHAR(128)    NULL COMMENT 'SHA-256 for deduplication',
    -- Source
    source_system   VARCHAR(50)     NULL COMMENT 'dese, apptegy, civicengage, boarddocs, youtube, manual, email',
    source_url      VARCHAR(1024)   NULL,
    -- People
    author          VARCHAR(255)    NULL,
    recipient       VARCHAR(255)    NULL,
    -- Primary organization (the one that published/owns this document)
    org_id          INT UNSIGNED    NULL,
    -- Dates
    document_date   DATE            NULL,
    published_date  DATE            NULL,
    -- Metadata
    language        VARCHAR(10)     NULL DEFAULT 'en',
    pages           INT UNSIGNED    NULL,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    updated_at      DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),

    INDEX idx_doc_family (doc_family),
    INDEX idx_doc_type (doc_type),
    INDEX idx_source_system (source_system),
    INDEX idx_org_id (org_id),
    INDEX idx_document_date (document_date),
    INDEX idx_file_hash (file_hash),
    INDEX idx_file_mime (file_mime),

    CONSTRAINT fk_documents_org
        FOREIGN KEY (org_id) REFERENCES organizations(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Polymorphic relationship table — one document can link to many entities
CREATE TABLE document_links (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    doc_id          INT UNSIGNED    NOT NULL,
    link_type       VARCHAR(50)     NOT NULL COMMENT 'parent, attachment, relates_to, supersedes, response_to',
    target_type     VARCHAR(50)     NOT NULL COMMENT 'document, case, organization, prs_intake, prr_request',
    target_id       INT UNSIGNED    NOT NULL,
    sort_order      INT             NOT NULL DEFAULT 0,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),

    UNIQUE KEY uq_doc_link (doc_id, link_type, target_type, target_id),
    INDEX idx_doc_id (doc_id),
    INDEX idx_target (target_type, target_id),

    CONSTRAINT fk_doclinks_doc
        FOREIGN KEY (doc_id) REFERENCES documents(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ingestion/scraping pipeline state
CREATE TABLE document_workflow (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    doc_id          INT UNSIGNED    NOT NULL UNIQUE,
    status          VARCHAR(50)     NOT NULL DEFAULT 'ingested' COMMENT 'ingested, classified, ocr_pending, reviewing, published, archived',
    confidence      DECIMAL(4,3)    NULL COMMENT 'Classification confidence 0.000–1.000',
    ocr_text        LONGTEXT        NULL COMMENT 'OCR-extracted text from images/PDFs',
    ocr_required    TINYINT(1)      NOT NULL DEFAULT 0,
    readability_score DECIMAL(5,2)  NULL,
    is_public       TINYINT(1)      NOT NULL DEFAULT 0,
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    reviewed_by     INT UNSIGNED    NULL,
    reviewed_at     DATETIME        NULL,
    review_notes    TEXT            NULL,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    updated_at      DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),

    INDEX idx_status (status),
    INDEX idx_is_public (is_public),
    INDEX idx_is_active (is_active),
    INDEX idx_reviewed_by (reviewed_by),

    CONSTRAINT fk_docworkflow_doc
        FOREIGN KEY (doc_id) REFERENCES documents(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_docworkflow_reviewer
        FOREIGN KEY (reviewed_by) REFERENCES admin_users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- FK-enforced junction for case ↔ document (the critical path).
-- document_links handles all other polymorphic relationships.
CREATE TABLE case_document_links (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    case_id         INT UNSIGNED    NOT NULL,
    doc_id          INT UNSIGNED    NOT NULL,
    sort_order      INT             NOT NULL DEFAULT 0,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),

    UNIQUE KEY uq_case_doc (case_id, doc_id),
    INDEX idx_case_id (case_id),
    INDEX idx_doc_id (doc_id),

    CONSTRAINT fk_cdl_case
        FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    CONSTRAINT fk_cdl_doc
        FOREIGN KEY (doc_id) REFERENCES documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- DESE DATA TABLES
-- All use org_id INT FK to organizations. NO string joins.
-- ============================================================================

CREATE TABLE restraint_data (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    org_id              INT UNSIGNED    NOT NULL COMMENT 'School organization',
    school_year         VARCHAR(10)     NOT NULL,
    school_name         VARCHAR(255)    NULL COMMENT 'Redundant with org_name, kept for import fidelity',
    enrollment          INT             NULL,
    students_restrained INT             NULL,
    total_restraints    INT             NULL,
    total_injuries      INT             NULL,
    total_restraints_suppressed TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'DESE suppression flag',
    created_at          DATETIME        NOT NULL DEFAULT NOW(),
    updated_at          DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),

    UNIQUE KEY uq_restraint_org_yr (org_id, school_year),
    INDEX idx_org_id (org_id),
    INDEX idx_school_year (school_year),

    CONSTRAINT fk_restraint_org
        FOREIGN KEY (org_id) REFERENCES organizations(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE discipline_data (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    org_id                  INT UNSIGNED    NOT NULL COMMENT 'District organization',
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

    UNIQUE KEY uq_discipline_org_yr (org_id, school_year),
    INDEX idx_org_id (org_id),
    INDEX idx_school_year (school_year),

    CONSTRAINT fk_discipline_org
        FOREIGN KEY (org_id) REFERENCES organizations(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE enrollment_data (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    org_id          INT UNSIGNED    NOT NULL COMMENT 'District organization',
    school_year     VARCHAR(10)     NOT NULL,
    high_needs_num  INT             NULL,
    high_needs_pct  DECIMAL(5,1)    NULL,
    el_num          INT             NULL COMMENT 'English Learner',
    el_pct          DECIMAL(5,1)    NULL,
    flne_num        INT             NULL COMMENT 'First Language Not English',
    flne_pct        DECIMAL(5,1)    NULL,
    low_income_num  INT             NULL,
    low_income_pct  DECIMAL(5,1)    NULL,
    sped_num        INT             NULL,
    sped_pct        DECIMAL(5,1)    NULL,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    updated_at      DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),

    UNIQUE KEY uq_enrollment_org_yr (org_id, school_year),
    INDEX idx_org_id (org_id),
    INDEX idx_school_year (school_year),

    CONSTRAINT fk_enrollment_org
        FOREIGN KEY (org_id) REFERENCES organizations(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE attendance_data (
    id                          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    org_id                      INT UNSIGNED    NOT NULL COMMENT 'District organization',
    school_year                 VARCHAR(10)     NOT NULL,
    attendance_rate             DECIMAL(5,1)    NULL,
    avg_absences                DECIMAL(5,1)    NULL,
    absent_10_plus_pct          DECIMAL(5,1)    NULL,
    chronically_absent_10_pct   DECIMAL(5,1)    NULL,
    chronically_absent_20_pct   DECIMAL(5,1)    NULL,
    created_at                  DATETIME        NOT NULL DEFAULT NOW(),
    updated_at                  DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),

    UNIQUE KEY uq_attendance_org_yr (org_id, school_year),
    INDEX idx_org_id (org_id),
    INDEX idx_school_year (school_year),

    CONSTRAINT fk_attendance_org
        FOREIGN KEY (org_id) REFERENCES organizations(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sped_results (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    org_id                  INT UNSIGNED    NOT NULL COMMENT 'District organization',
    school_year             VARCHAR(10)     NOT NULL,
    sped_grad_rate          DECIMAL(5,1)    NULL,
    sped_dropout_rate       DECIMAL(5,1)    NULL,
    lre_full_incl_pct       DECIMAL(5,1)    NULL COMMENT '% in full inclusion (LRE A)',
    parent_involve_pct      DECIMAL(5,1)    NULL,
    post_school_engage_pct  DECIMAL(5,1)    NULL,
    created_at              DATETIME        NOT NULL DEFAULT NOW(),
    updated_at              DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),

    UNIQUE KEY uq_sped_results_org_yr (org_id, school_year),
    INDEX idx_org_id (org_id),
    INDEX idx_school_year (school_year),

    CONSTRAINT fk_sped_results_org
        FOREIGN KEY (org_id) REFERENCES organizations(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE prs_data (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    org_id              INT UNSIGNED    NOT NULL COMMENT 'District organization',
    school_year         VARCHAR(10)     NOT NULL,
    prs_level           VARCHAR(50)     NULL,
    prs_rating          VARCHAR(50)     NULL,
    indicator_1_score   DECIMAL(5,1)    NULL,
    indicator_2_score   DECIMAL(5,1)    NULL,
    indicator_3_score   DECIMAL(5,1)    NULL,
    indicator_4_score   DECIMAL(5,1)    NULL,
    indicator_5_score   DECIMAL(5,1)    NULL,
    indicator_6_score   DECIMAL(5,1)    NULL,
    indicator_7_score   DECIMAL(5,1)    NULL,
    criterion_1_score   DECIMAL(5,1)    NULL,
    criterion_2_score   DECIMAL(5,1)    NULL,
    created_at          DATETIME        NOT NULL DEFAULT NOW(),
    updated_at          DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),

    UNIQUE KEY uq_prs_data_org_yr (org_id, school_year),
    INDEX idx_org_id (org_id),
    INDEX idx_school_year (school_year),
    INDEX idx_prs_level (prs_level),

    CONSTRAINT fk_prs_data_org
        FOREIGN KEY (org_id) REFERENCES organizations(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE teacher_salaries (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    org_id          INT UNSIGNED    NOT NULL COMMENT 'District organization',
    school_year     VARCHAR(10)     NOT NULL,
    salary_totals   DECIMAL(14,2)   NULL,
    average_salary  DECIMAL(10,2)   NULL,
    fte_count       DECIMAL(8,1)    NULL,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    updated_at      DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),

    UNIQUE KEY uq_teacher_salaries_org_yr (org_id, school_year),
    INDEX idx_org_id (org_id),
    INDEX idx_school_year (school_year),

    CONSTRAINT fk_teacher_salaries_org
        FOREIGN KEY (org_id) REFERENCES organizations(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PRS / PRR / SPED COMPLAINT TRACKING
-- Individual complaint-level data. org_id resolved from name at import time.
-- ============================================================================

CREATE TABLE prs_intakes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prs_number      VARCHAR(50)     NULL,
    org_id          INT UNSIGNED    NULL COMMENT 'Resolved from district_agency name at import',
    intake_date     DATE            NULL,
    status          VARCHAR(50)     NULL COMMENT 'open, investigating, findings_issued, closed',
    findings_date   DATE            NULL,
    category        VARCHAR(100)    NULL,
    subcategory     VARCHAR(100)    NULL,
    closure_code    VARCHAR(50)     NULL,
    raw_agency_name VARCHAR(255)    NULL COMMENT 'Original name from source (for debugging)',
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    updated_at      DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),

    INDEX idx_prs_number (prs_number),
    INDEX idx_org_id (org_id),
    INDEX idx_status (status),
    INDEX idx_intake_date (intake_date),
    INDEX idx_category (category),

    CONSTRAINT fk_prs_intakes_org
        FOREIGN KEY (org_id) REFERENCES organizations(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- PRS Tracker tables
-- ────────────────────────────────────────────────────────────

CREATE TABLE prs_cases (
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
    statutory_deadline      DATE            NULL COMMENT '60 calendar days from acceptance per DESE',
    extended_deadline       DATE            NULL,
    actual_resolution_date  DATE            NULL,
    overdue_at_filing       TINYINT(1)      NOT NULL DEFAULT 0,
    days_to_acceptance      INT             NULL,
    days_to_findings        INT             NULL,
    total_days_open         INT             NULL,
    created_at              DATETIME        NOT NULL DEFAULT NOW(),
    updated_at              DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    INDEX idx_prs_number (prs_number),
    INDEX idx_org_id (org_id),
    INDEX idx_current_status (current_status),
    INDEX idx_filing_date (filing_date),
    CONSTRAINT fk_prs_cases_org FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE prs_events (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE prs_findings (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE prs_categories (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_name   VARCHAR(100)    NOT NULL,
    category_slug   VARCHAR(100)    NOT NULL UNIQUE,
    parent_id       INT UNSIGNED    NULL,
    description     TEXT            NULL,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    updated_at      DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    INDEX idx_parent_id (parent_id),
    CONSTRAINT fk_prs_categories_parent FOREIGN KEY (parent_id) REFERENCES prs_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sped_complaints (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    complaint_number    VARCHAR(50)     NULL,
    org_id              INT UNSIGNED    NULL,
    intake_date         DATE            NULL,
    status              VARCHAR(50)     NULL,
    findings_date       DATE            NULL,
    category            VARCHAR(100)    NULL,
    subcategory         VARCHAR(100)    NULL,
    closure_code        VARCHAR(50)     NULL,
    raw_agency_name     VARCHAR(255)    NULL,
    created_at          DATETIME        NOT NULL DEFAULT NOW(),
    updated_at          DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),

    INDEX idx_complaint_number (complaint_number),
    INDEX idx_org_id (org_id),
    INDEX idx_status (status),
    INDEX idx_intake_date (intake_date),

    CONSTRAINT fk_sped_complaints_org
        FOREIGN KEY (org_id) REFERENCES organizations(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE prr_tracker (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_name            VARCHAR(512)    NULL,
    matter_type             VARCHAR(100)    NULL,
    org_id                  INT UNSIGNED    NULL COMMENT 'Agency the request was sent to',
    stage                   VARCHAR(50)     NULL COMMENT 'drafting, submitted, awaiting_response, appealed, spr_review, closed',
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
    raw_agency_name         VARCHAR(255)    NULL,
    created_at              DATETIME        NOT NULL DEFAULT NOW(),
    updated_at              DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),

    INDEX idx_org_id (org_id),
    INDEX idx_stage (stage),
    INDEX idx_request_date (request_date),

    CONSTRAINT fk_prr_tracker_org
        FOREIGN KEY (org_id) REFERENCES organizations(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- DATA CATALOG & SYNC
-- ============================================================================

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

CREATE TABLE sync_log (
    dataset             VARCHAR(50)     NOT NULL,
    source_url          TEXT            NULL,
    last_synced         DATETIME        NULL,
    row_count           INT             NULL,
    source_last_updated VARCHAR(100)    NULL,
    status              VARCHAR(50)     NULL DEFAULT 'success',
    error_message       TEXT            NULL,
    created_at          DATETIME        NOT NULL DEFAULT NOW(),
    updated_at          DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),

    PRIMARY KEY (dataset)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- ADMIN / AUTH
-- ============================================================================

CREATE TABLE admin_users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(100)    NOT NULL UNIQUE,
    email           VARCHAR(255)    NOT NULL UNIQUE,
    display_name    VARCHAR(255)    NULL,
    password_hash   VARCHAR(255)    NOT NULL,
    role            VARCHAR(50)     NOT NULL DEFAULT 'editor' COMMENT 'superadmin, admin, editor',
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    last_login_at   DATETIME        NULL,
    last_login_ip   VARCHAR(45)     NULL,
    login_attempts  INT UNSIGNED    NOT NULL DEFAULT 0,
    locked_until    DATETIME        NULL,
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

    CONSTRAINT fk_admin_sessions_user
        FOREIGN KEY (admin_user_id) REFERENCES admin_users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_log (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_user_id   INT UNSIGNED    NULL,
    action          VARCHAR(100)    NOT NULL COMMENT 'login, logout, create, update, delete, publish, unpublish',
    entity_type     VARCHAR(100)    NULL,
    entity_id       INT UNSIGNED    NULL,
    entity_label    VARCHAR(512)    NULL COMMENT 'Human-readable label of the entity',
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

    CONSTRAINT fk_audit_log_user
        FOREIGN KEY (admin_user_id) REFERENCES admin_users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE system_config (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    config_key      VARCHAR(100)    NOT NULL UNIQUE,
    config_value    TEXT            NULL,
    config_type     VARCHAR(50)     NOT NULL DEFAULT 'string' COMMENT 'string, integer, boolean, json',
    description     VARCHAR(512)    NULL,
    is_public       TINYINT(1)      NOT NULL DEFAULT 0,
    created_at      DATETIME        NOT NULL DEFAULT NOW(),
    updated_at      DATETIME        NOT NULL DEFAULT NOW() ON UPDATE NOW(),

    UNIQUE KEY uq_config_key (config_key),
    INDEX idx_is_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SEED DATA
-- ============================================================================

-- Default admin user (password: admin — CHANGE IN PRODUCTION)
INSERT IGNORE INTO admin_users (id, username, email, display_name, password_hash, role, is_active)
VALUES (1, 'admin', 'admin@parentdataforce.com', 'Site Administrator',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'superadmin', 1);

-- System configuration defaults
INSERT IGNORE INTO system_config (config_key, config_value, config_type, description, is_public) VALUES
('site_name',              'Parent Data Force',     'string',  'Public-facing site name',                    1),
('site_tagline',           'MAKING DATA MAKE SENSE','string',  'Site tagline / subtitle',                     1),
('site_description',       'Data-driven advocacy for families navigating special education and public systems. Tracking complaints, records, outcomes, and public accountability across Massachusetts.', 'string', 'Default meta description', 1),
('admin_email',            'admin@parentdataforce.com', 'string', 'Contact email for site notifications',  0),
('records_per_page',       '25',                    'integer', 'Default pagination size',                    0),
('max_records_per_page',   '100',                   'integer', 'Maximum pagination size',                    0),
('default_timeline_years', '3',                     'integer', 'Default years shown on dashboards',          0),
('maintenance_mode',       '0',                     'boolean', 'Enable maintenance mode',                    0),
('google_analytics_id',    '',                      'string',  'Google Analytics measurement ID',             0),
('session_lifetime_hours', '8',                     'integer', 'Admin session lifetime in hours',            0),
('max_login_attempts',     '5',                     'integer', 'Failed login attempts before lockout',       0),
('login_lockout_minutes',  '15',                    'integer', 'Lockout duration after max attempts',        0);

SET FOREIGN_KEY_CHECKS = 1;
