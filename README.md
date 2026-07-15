# Parent Data Force — Project Root

`C:\projects\pdf-website\`

## Quick Start

1. Start MariaDB (if using portable): `C:\mariadb\bin\mysqld --port=3307`
2. Start dev server: `dev\start.bat`
3. Open http://localhost:8081

## What's Here

| Directory | Contents |
|-----------|----------|
| `data\` | All DESE exports (17 XLS files), seed SQL, reference documents. **Immutable source data.** |
| `docs\` | Rough-draft documentation for the schema designer. **Read in order: 01 through 05.** |
| `branding\` | Logo (1024x1024 PNG), complete brand reference (colors, fonts, buttons, tokens). |
| `dev\` | Working PHP 8.2.32 application. The rebuild happens here. |

## For the Schema Designer (Next Session)

1. Read `docs\01-data-inventory.md` — what data exists
2. Read `docs\02-document-types.md` — every document type in PRS/PRR workflows
3. Read `docs\03-current-routes.md` — what the site does today (28 routes)
4. Read `docs\04-data-relationships.md` — every inconsistency to fix
5. Read `docs\05-styling-blueprint.md` — CSS architecture to implement
6. Design the new schema as migration SQL files
7. The `organizations` table is the hub — start there

## Key Decisions for the Rebuild

- **organizations** is the central hub — 17 org types, 7 DESE data tables, cases, documents, PRS, PRR
- All string-based joins (`org_code`, `district_code`) must become INT FK joins (`org_id`)
- PRS intakes and PRR tracker use NAME-based linking — needs name→code mapping
- Two parallel document systems exist (ingest + scraper) — must unify
- 44-column documents table — should split into content/links/workflow
- CSS: global.css + elements/ + pages/ — loaded per-controller, no monolith
- Charts: Chart.js 4.4.0 from CDN (already in production CSP)
- Server: LiteSpeed, PHP 8.x, MariaDB, FTP-only deployment, no build tools
- Dark theme only — no light mode
- No HTML files — everything PHP

## Environment

- PHP 8.2.32 portable: `dev\php\php.exe` (all extensions in `dev\php\php.ini`)
- MariaDB 10.11+: `127.0.0.1:3307`, database `pdf_db`, user `pdf_user`/`dev_password`
- `.env` at `dev\.env` with DB credentials
- One-click dev: `dev\start.bat`
