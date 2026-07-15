# 01 — Data Inventory

Generated 2026-07-14. Lists every data file collected for the schema designer.

## DESE Organization Exports (17 files)

All from Downloads, dated 2026-07-14.

| # | File | Size | Org Type |
|---|------|------|----------|
| 1 | dese organization search - public schools export.xls | 610KB | Public School |
| 2 | dese organization search public school districts export.xls | 184KB | Public School District |
| 3 | dese organization search charter public school export.xls | 30KB | Charter School |
| 4 | dese organization search collaborative programs export.xls | 59KB | Collaborative Program |
| 5 | dese organization search private schools export.xls | 157KB | Private School |
| 6 | dese organization search approved special education programs export.xls | 59KB | Approved SPED Program |
| 7 | dese organization search approved special education school export.xls | 38KB | Approved SPED School |
| 8 | dese organization search approved special education agency export.xls | 25KB | Approved SPED Agency |
| 9 | dese organization search unapproved special education school export.xls | 18KB | Unapproved SPED School |
| 10 | dese organization search title 1 status district export.xls | 176KB | Title 1 District |
| 11 | dese organization search title 1 status school export.xls | 621KB | Title 1 School |
| 12 | dese organization search chapter 74 career voc tech education export.xls | 59KB | Career/Voc Tech |
| 13 | dese organization search innovation schools and academies export.xls | 13KB | Innovation School |
| 14 | dese organization search alternative education school export.xls | 10KB | Alt Ed School |
| 15 | dese organization search alternative education programs export.xls | 25KB | Alt Ed Program |
| 16 | dese organization search tribal education agency export.xls | 1KB | Tribal |
| 17 | dese organization search educator preparation program provider (EPPP) export.xls | 29KB | EPPP |

Common columns: Org Code, Org Name, Town. Type-specific: Grade Span, Function Area, Title 1 Status.

## Seed SQL Files (9 files)

From current project backend/: schema.sql, seed.sql, seed_restraint.sql, seed_prs.sql, seed_discipline.sql, seed_enrollment.sql, seed_attendance.sql, seed_sped.sql, seed_drive_data.sql

## Reference Documents

50550000.docx (161KB) — possible PRR response or advocacy document.

## Key Observations

- 17 org types in one organizations table via tags JSON
- String-based joins (org_code VARCHAR) instead of FKs on DESE data tables
- PRS data uses district_agency NAME not code
- PRR tracker uses agency NAME not code
- Two parallel document systems (ingest pipeline + scraper) with different schemas
