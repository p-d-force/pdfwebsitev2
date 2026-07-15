# Continuation Plan — Parent Data Force v2

## Context

The core platform is built: 25+ routes, 4 choropleth maps, 6 data browsers, full PRS tracker, county/town infrastructure, accessibility suite, export pipeline. Scouts confirmed: PRS all working, explorer has year animation (already done), discipline data is cloned across years (source data issue, not fixable without new DESE imports). Two bugs found: town maps lack smooth gradient toggle (county maps have it), and admin 404 pages are broken (renderAdmin path mismatch). This plan fixes those bugs and adds one high-impact polish item.

## Approach

### Step 1: Add smooth gradient to town map controllers

Both town map controllers (`DataPortalController::townMap()` and `PrsController::townMap()`) only compute discrete colors. Extend them to also compute HSL smooth colors, matching the county map pattern.

**DataPortalController::townMap()** (`dev/app/Controllers/DataPortalController.php`, method starts ~line 527):
- After computing `$p33`/`$p66` (lines ~563), add `$min`/`$max`/`$range` from `$rates` array (same as county map lines 496-498).
- In the `foreach` loop (line ~567), add `$ratio = ($rate - $min) / ($range ?: 1); $hue = round(120 * (1 - $ratio)); $scolor = "hsl({$hue}, 70%, 45%)";` and store as `'smooth' => $scolor` in the colors array.
- The existing `$colors[$slug]` array at line 575 already has `'color' => $color`; add the `'smooth' => $scolor` key.

**PrsController::townMap()** (`dev/app/Controllers/PrsController.php`, method starts ~line 1865):
- Same pattern — compute `$min`/`$max`/`$range` from `$counts` array, add `$ratio`/`$hue`/`$scolor` in the foreach, append `'smooth' => $scolor` to the colors array.

No new dependencies, no schema changes, no new files.

### Step 2: Add smooth toggle to town map views

Both town map views need the same toggle infrastructure as county maps: a "Smooth Gradient" button, dual legends, and `toggleMapMode()` JS.

**`dev/app/views/data/town-map.php`**:
- Add the toggle button (`<button id="modeToggle" ...>Smooth Gradient</button>`) after the year selector form, matching the county map view pattern exactly.
- Add the `<div id="legendSmooth">` element (hidden by default) with HSL-based swatches.
- Replace the monolithic JS block with the county map's JS pattern: `smoothMode` flag, `toggleMapMode()` function, `applyColors()` function, and use `colors[slug].smooth` for the smooth fill.
- The JS iterates over `town_colors` (JSON-encoded PHP `$town_colors`). The county map iterates over `county_colors`. The JS is identical except the variable name `colors` is set from `<?= json_encode($town_colors, ...) ?>`.

**`dev/app/views/prs/town-map.php`**:
- Same changes as data/town-map but with PRS-specific tooltip fields (`cases`, `open` instead of `rate`, `restraints`, `enrollment`).

The county map view at `dev/app/views/data/map.php` is the canonical template — copy its toggle infrastructure verbatim, changing only the data variable name and tooltip fields.

### Step 3: Fix admin 404 error pages

All 8 admin controllers call `View::renderAdmin('errors/404', ...)` which resolves to `views/admin/errors/404.php` — a file that does not exist. The error views are at `views/errors/404.php`.

**Fix**: Create `dev/app/views/admin/errors/404.php` with a single include:
```php
<?php require __DIR__ . '/../../errors/404.php'; ?>
```

This keeps the admin user in the admin layout context (since `renderAdmin` uses `admin/_layout.php` with sidebar) while reusing the existing error page markup. The 404 page just shows a heading and link — no admin-specific content needed.

No controller changes required. The 8 controllers that call `renderAdmin('errors/404', ...)` are: AdminArticlesController, AdminCasesController, AdminDocumentsController, AdminMediaController, AdminOrganizationsController, AdminPrsController, AdminSubmissionsController, AdminUpdatesController — all line references in the grep above.

### Step 4: Mark discipline data as known limitation

The discipline seed data (`data/seeds/seed_discipline.sql`) contains 2,779 rows cloned across 7 school years — every district has identical values for all years. The source is a single DESE SSDR snapshot (2018-19). All charts and API endpoints work correctly with whatever data is present; they just show identical values across years.

**Action**: No code changes. The discipline chart Y-axis label was already changed to "Students" in a prior session. The `ROUND(SUM(students * pct_*/100))` query pattern is correct and will immediately show real variation when multi-year data is imported. Document this as a data issue in the help page (`dev/app/views/data/help.php`) by adding a note to the Discipline dataset row: append `(single-year data — year-over-year comparison unavailable)` to the years column.

### Step 5: Document page update

Update `dev/app/views/data/help.php`:
- Discipline dataset row: change "2018-2025" to "2018-2025 (single-year snapshot)"
- Add a bullet under Tips: "Discipline data currently shows a single DESE snapshot replicated across years. Year-over-year comparison will become available when multi-year data is imported."

## Critical files & anchors

| File | Region | Why |
|------|--------|-----|
| `dev/app/Controllers/DataPortalController.php` | `townMap()` method, lines 556-582 | Add smooth HSL color computation — copy pattern from `map()` lines 504-509 |
| `dev/app/Controllers/PrsController.php` | `townMap()` method, lines 1865-1915 | Same smooth HSL addition |
| `dev/app/views/data/town-map.php` | Entire file (~58 lines) | Add toggle button, dual legend, toggleMapMode JS — copy from `data/map.php` |
| `dev/app/views/prs/town-map.php` | Entire file (~55 lines) | Same toggle additions with PRS tooltip fields |
| `dev/app/views/admin/errors/404.php` | New file — 1 line | Include the public 404 view to fix admin 404s |

## Verification

1. **Town map smooth toggle**: Visit `/data/town-map` → click "Smooth Gradient" button → counties change from discrete green/orange/red to continuous HSL hues. Repeat for `/prs/town-map`.
2. **Admin 404**: Visit `/admin/organizations/99999/edit` (nonexistent ID) → should show styled 404 page within admin layout (with sidebar), not a raw PHP error.
3. **Help page**: Visit `/data/help` → discipline dataset row shows "(single-year snapshot)" annotation.

## Assumptions & contingencies

- The smooth toggle JS uses `colors[slug].smooth` — if `smooth` key is missing (legacy data), the fallback `(colors[slug].smooth || colors[slug].color)` in `applyColors()` handles it. No migration needed for existing color data.
- If the admin 404 include path is wrong, the `RuntimeException` from View.php will surface it immediately. The path `__DIR__ . '/../../errors/404.php'` resolves to `views/errors/404.php` from `views/admin/errors/404.php` — verify with a quick test after creating.
