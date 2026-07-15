-- ============================================================================
-- DESE Data Migration: old seed SQL (org_code) → new schema (org_id)
-- Run after importing organizations from DESE exports
-- ============================================================================

-- ── Restraint Data ──
CREATE TEMPORARY TABLE _restraint_old (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    org_code VARCHAR(10) NOT NULL,
    school_year VARCHAR(10) NOT NULL,
    enrollment INT NULL,
    students_restrained INT NULL,
    total_restraints INT NULL,
    total_injuries INT NULL,
    restraint_rate_per_100 DECIMAL(8,2) NULL,
    injuries_per_restraint DECIMAL(8,4) NULL,
    school_name VARCHAR(255) NULL,
    total_restraints_suppressed TINYINT(1) NULL DEFAULT 0
) ENGINE=MEMORY;

LOAD DATA INFILE 'C:/projects/pdf-website/data/seeds/seed_restraint.sql'
INTO TABLE _restraint_old
FIELDS TERMINATED BY ',' ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 5 LINES;
