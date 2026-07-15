# 03 — Current Routes & Data Dependencies

All 28 routes from the current application, with their controllers, database tables, and known issues.

## Content Pages

| Route | Controller::method | Tables | Notes |
|-------|-------------------|--------|-------|
| `/` | HomeController::index | articles, cases, organizations | 5 separate queries. Hero stats use COUNT. District section has subquery with `district_code` bug. |
| `/articles/` | ArticleController::list | articles | Simple SELECT with ORDER BY |
| `/articles/{slug}` | ArticleController::show | articles | Single fetch by slug |
| `/cases/` | CaseController::list | cases | Filter bar: search, district, type, status |
| `/cases/{slug}` | CaseController::show | cases | Fallback: tries slug then case_number |
| `/districts/` | DistrictController::list | organizations, cases | LEFT JOIN with case counts, GROUP BY |
| `/districts/{slug}` | DistrictController::show | organizations, cases, articles, restraint_data, documents | Complex dashboard. Uses `org_code` string joins on DESE data. |
| `/about/` | HomeController::about | none | Static content |
| `/submit/` | HomeController::submit | submissions | 3-tab form: Tip, Help, Upload |
| `/updates/` | HomeController::updates | updates | Simple SELECT, LIMIT 20 |
| `/appearances/` | HomeController::appearances | media_appearances | Simple SELECT, ORDER BY date |
| `/resources/` | HomeController::resources | none | Static resource cards |
| `/donate/` | HomeController::donate | none | Static donation tiers |

## Data Portal

| Route | Controller::method | Tables | Notes |
|-------|-------------------|--------|-------|
| `/data/` | DataPortalController::index | restraint_data, discipline_data, enrollment_data, attendance_data, prs_data | Hub with dataset cards + record counts |
| `/data/?tab=restraint` | DataPortalController::index | restraint_data | Filtered by school_year, district |
| `/data/?tab=trends` | DataPortalController::index | restraint_data | Chart.js canvas — data from /api/data |
| `/data/?tab=compare` | DataPortalController::index | restraint_data, organizations | Multi-district comparison |
| `/data/restraint` | DataSubController::restraint | restraint_data, organizations | Paginated (50/page). Uses org_code string join. |
| `/data/prs` | DataSubController::prs | prs_data, organizations | Same pattern as restraint |
| `/data/discipline` | DataSubController::discipline | discipline_data | District-level, school_year filter |
| `/data/enrollment` | DataSubController::enrollment | enrollment_data | Demographics with SPED/EL/low-income pcts |
| `/data/attendance` | DataSubController::attendance | attendance_data | Attendance rates, chronic absenteeism |
| `/data/sped-results` | DataSubController::spedResults | sped_results | SPED grad rates, dropout rates |

## Other

| Route | Controller | Tables |
|-------|-----------|--------|
| `/search/` | SearchController::index | articles, cases |
| `/compare` | DataPortalController::compare | restraint_data, organizations |
| `/documents/{id}` | HomeController::document | documents, document_tags, organizations |

## API Endpoints (JSON)

| Route | Purpose | Tables |
|-------|---------|--------|
| `/api/data` | Chart data for homepage/data portal | restraint_data, etc. |
| `/api/cases` | Case JSON | cases |
| `/api/articles` | Article JSON | articles |
| `/api/search` | Search suggestions | articles, cases |
| `/api/submit` | Form POST handler | submissions |
| `/api/subscribe` | Newsletter signup | (external) |

## Feeds

| Route | Purpose |
|-------|---------|
| `/rss` | RSS feed of articles + cases |
| `/sitemap.xml` | XML sitemap |

## Key Issues for Schema Designer

1. Homepage makes 5 separate queries (featured articles, recent cases, 3 COUNTs)
2. District pages use `org_code` string joins on DESE data (should be `org_id` FK)
3. `/data/restraint` and similar use `JOIN ON r.org_code = o.org_code` — string, no FK
4. `cases.district_id` FK exists but some queries use `district_code` string
5. Search uses LIKE, not FULLTEXT
6. No admin panel routes — admin_users/audit_log tables exist but unwired
