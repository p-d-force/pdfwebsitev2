#!/usr/bin/env python3
"""DESE Data Migration: old seed SQL → new schema. Uses regular staging tables."""
import subprocess, re

MYSQL = r"C:/projects/pdf-website/mariadb-portable/mariadb-11.4.5-winx64/bin/mysql.exe"
OPTS = ["-u", "root", "-h", "127.0.0.1", "-P", "3307", "--skip-password", "pdf_db"]
SEED = "C:/projects/pdf-website/data/seeds"

def run(sql):
    p = subprocess.run([MYSQL] + OPTS + ["-e", sql], capture_output=True, text=True)
    if p.returncode != 0 and "WARNING" not in p.stderr:
        print(f"  ERR: {p.stderr[:200]}")
    return p

def load_seed(stage_table, orig_table, seed_name):
    """Create stage table, pipe old INSERTs into it."""
    path = f"{SEED}/{seed_name}.sql"
    with open(path, encoding='utf-8', errors='replace') as f:
        content = f.read()
    # Strip DDL before first INSERT
    idx = content.find('INSERT')
    if idx > 0:
        content = content[idx:]
    # Redirect INSERTs to stage table
    content = re.sub(rf'INSERT( IGNORE)? INTO `?{orig_table}`?', f'INSERT INTO {stage_table}', content)
    p = subprocess.run([MYSQL] + OPTS, input=content, capture_output=True, text=True)
    ok = p.returncode == 0
    if not ok:
        print(f"  Load err: {p.stderr[:200]}")
    return ok

# ── Restraint ──
print("--- restraint_data ---")
run("DROP TABLE IF EXISTS _s_r")
run("CREATE TABLE _s_r (id INT PRIMARY KEY AUTO_INCREMENT, school_year VARCHAR(10), district_name VARCHAR(255), district_code VARCHAR(10), school_name VARCHAR(255), school_code VARCHAR(10), enrollment INT, students_restrained TINYINT, students_restrained_suppressed TINYINT, total_restraints INT, total_restraints_suppressed TINYINT, total_injuries INT, total_injuries_suppressed TINYINT, restraint_rate_per_100 DECIMAL(8,2), injuries_per_restraint DECIMAL(8,4), is_summary_row TINYINT, source_workbook VARCHAR(255)) ENGINE=MEMORY")
if load_seed("_s_r", "restraint_data", "seed_restraint"):
    run("INSERT INTO restraint_data (org_id, school_year, school_name, enrollment, students_restrained, total_restraints, total_injuries) SELECT o.id, r.school_year, r.school_name, r.enrollment, r.students_restrained, r.total_restraints, r.total_injuries FROM _s_r r JOIN organizations o ON o.org_code = r.school_code ON DUPLICATE KEY UPDATE enrollment=VALUES(enrollment)")
    run("DROP TABLE _s_r")
    print(f"  Rows: {run('SELECT COUNT(*) FROM restraint_data').stdout.strip().split(chr(10))[-1]}")
else:
    run("DROP TABLE _s_r")

# ── Discipline ──
print("--- discipline_data ---")
run("DROP TABLE IF EXISTS _s_d")
run("CREATE TABLE _s_d (id INT PRIMARY KEY AUTO_INCREMENT, district_name VARCHAR(255), district_code VARCHAR(10), school_year VARCHAR(10), students INT, students_disciplined INT, pct_in_school_susp DECIMAL(5,2), pct_out_school_susp DECIMAL(5,2), pct_expulsion DECIMAL(5,2), pct_alt_setting DECIMAL(5,2), pct_emergency_removal DECIMAL(5,2), pct_arrest DECIMAL(5,2), pct_law_enforce DECIMAL(5,2)) ENGINE=MEMORY")
if load_seed("_s_d", "discipline_data", "seed_discipline"):
    run("INSERT INTO discipline_data (org_id, school_year, students, students_disciplined, pct_in_school_susp, pct_out_school_susp, pct_expulsion, pct_alt_setting, pct_emergency_removal, pct_arrest, pct_law_enforce) SELECT o.id, d.school_year, d.students, d.students_disciplined, d.pct_in_school_susp, d.pct_out_school_susp, d.pct_expulsion, d.pct_alt_setting, d.pct_emergency_removal, d.pct_arrest, d.pct_law_enforce FROM _s_d d JOIN organizations o ON o.org_code = d.district_code ON DUPLICATE KEY UPDATE students=VALUES(students)")
    run("DROP TABLE _s_d")

# ── Enrollment ──
print("--- enrollment_data ---")
run("DROP TABLE IF EXISTS _s_e")
run("CREATE TABLE _s_e (id INT PRIMARY KEY AUTO_INCREMENT, district_name VARCHAR(255), district_code VARCHAR(10), school_year VARCHAR(10), high_needs_num INT, high_needs_pct DECIMAL(5,1), el_num INT, el_pct DECIMAL(5,1), flne_num INT, flne_pct DECIMAL(5,1), low_income_num INT, low_income_pct DECIMAL(5,1), sped_num INT, sped_pct DECIMAL(5,1)) ENGINE=MEMORY")
if load_seed("_s_e", "enrollment_data", "seed_enrollment"):
    run("INSERT INTO enrollment_data (org_id, school_year, high_needs_num, high_needs_pct, el_num, el_pct, flne_num, flne_pct, low_income_num, low_income_pct, sped_num, sped_pct) SELECT o.id, e.school_year, e.high_needs_num, e.high_needs_pct, e.el_num, e.el_pct, e.flne_num, e.flne_pct, e.low_income_num, e.low_income_pct, e.sped_num, e.sped_pct FROM _s_e e JOIN organizations o ON o.org_code = e.district_code ON DUPLICATE KEY UPDATE sped_pct=VALUES(sped_pct)")
    run("DROP TABLE _s_e")

# ── Attendance ──
print("--- attendance_data ---")
run("DROP TABLE IF EXISTS _s_a")
run("CREATE TABLE _s_a (id INT PRIMARY KEY AUTO_INCREMENT, district_name VARCHAR(255), district_code VARCHAR(10), school_year VARCHAR(10), attendance_rate DECIMAL(5,1), avg_absences DECIMAL(5,1), absent_10_plus_pct DECIMAL(5,1), chronically_absent_10_pct DECIMAL(5,1), chronically_absent_20_pct DECIMAL(5,1)) ENGINE=MEMORY")
if load_seed("_s_a", "attendance_data", "seed_attendance"):
    run("INSERT INTO attendance_data (org_id, school_year, attendance_rate, avg_absences, absent_10_plus_pct, chronically_absent_10_pct, chronically_absent_20_pct) SELECT o.id, a.school_year, a.attendance_rate, a.avg_absences, a.absent_10_plus_pct, a.chronically_absent_10_pct, a.chronically_absent_20_pct FROM _s_a a JOIN organizations o ON o.org_code = a.district_code ON DUPLICATE KEY UPDATE attendance_rate=VALUES(attendance_rate)")
    run("DROP TABLE _s_a")

# ── SPED Results ──
print("--- sped_results ---")
run("DROP TABLE IF EXISTS _s_sped")
run("CREATE TABLE _s_sped (id INT PRIMARY KEY AUTO_INCREMENT, district_name VARCHAR(255), district_code VARCHAR(10), school_year VARCHAR(10), sped_grad_rate DECIMAL(5,1), sped_dropout_rate DECIMAL(5,1), lre_full_incl_pct DECIMAL(5,1), parent_involve_pct DECIMAL(5,1)) ENGINE=InnoDB")
if load_seed("_s_sped", "sped_results", "seed_sped"):
    run("INSERT INTO sped_results (org_id, school_year, sped_grad_rate, sped_dropout_rate, lre_full_incl_pct, parent_involve_pct) SELECT o.id, s.school_year, s.sped_grad_rate, s.sped_dropout_rate, s.lre_full_incl_pct, s.parent_involve_pct FROM _s_sped s JOIN organizations o ON o.org_code = s.district_code ON DUPLICATE KEY UPDATE sped_grad_rate=VALUES(sped_grad_rate)")
    run("DROP TABLE _s_sped")

# ── PRS Intakes (name-based, org_id left NULL) ──
print("--- prs_intakes ---")
run("DROP TABLE IF EXISTS _s_prs")
run("CREATE TABLE _s_prs (id INT PRIMARY KEY AUTO_INCREMENT, prs_number VARCHAR(50), district VARCHAR(255), intake_date DATE, status VARCHAR(50), findings_date DATE, category VARCHAR(100), subcategory VARCHAR(100), closure_code VARCHAR(50)) ENGINE=InnoDB")
if load_seed("_s_prs", "prs_intakes_data", "seed_prs"):
    run("INSERT INTO prs_intakes (prs_number, intake_date, status, findings_date, category, subcategory, closure_code, raw_agency_name) SELECT p.prs_number, p.intake_date, p.status, p.findings_date, p.category, p.subcategory, p.closure_code, p.district FROM _s_prs p")
    run("DROP TABLE _s_prs")

# Final counts
for t in ["restraint_data", "discipline_data", "enrollment_data", "attendance_data", "sped_results", "prs_intakes"]:
    r = run(f"SELECT COUNT(*) FROM {t}")
    print(f"  {t}: {r.stdout.strip().split(chr(10))[-1]} rows")

print("Done.")
