# Master Orchestration Plan — All Systems

## Context

Three implementation plans exist and must be executed as one unified delivery:
- **`local://data-viz-plan.md`** — 20 phases, 310+ subphases: charts, dashboards, school profiles, SVG heatmaps, county views
- **`local://prs-tracker-plan.md`** — 10 phases, 300 subphases: PRS case tracker, timelines, analytics, cross-reference

Total: 30 phases, 610+ subphases. This plan sequences them to maximize parallelism while respecting hard dependencies. The Chart component (data-viz Phase 1) gates everything. The SVG map (data-viz Phases 11-20) gates PRS heatmap. The PRS data model (PRS Phase 1) gates all PRS work.

Vision verification: after each visual deliverable (charts, maps, dashboards), capture a screenshot and analyze with OpenRouter Gemini Flash (`google/gemini-2.5-flash`) to confirm correct rendering, dark-theme colors, layout, and data display. Model: `openrouter/google/gemini-2.5-flash`. Prompt template: "Verify this page matches a dark-theme data dashboard. Check: (1) correct colors — orange accents #ff5a1f, dark backgrounds #0b0b0b, (2) no broken layouts or overlapping elements, (3) data values visible and correctly formatted, (4) all chart axes labeled. Report any issues."

## Approach

### Wave 0: Foundation (parallel)

Run these two together — no dependency between them:

| Task | Plan Ref | Output |
|------|----------|--------|
| **A1** | data-viz 1.1-1.20 | `Chart.php` component + all chart API endpoints + color system + responsive defaults |
| **B1** | prs-tracker 1.1-1.6 | PRS tables (`prs_cases`, `prs_events`, `prs_findings`, `prs_categories`) + migration from `prs_intakes` |

**Gate**: `Chart::render()` outputs valid `<canvas>` + `<script>`. `prs_cases` table exists with migrated data. Vision check: render a test bar chart in browser, screenshot, Gemini Flash confirms correct orange/dark styling.

### Wave 1: Core Frontend (parallel)

A1 complete unlocks these. B1 complete unlocks C1.

| Task | Depends | Plan Ref | Output |
|------|---------|----------|--------|
| **A2** | A1 | data-viz 2.1-2.20 | All 6 data browser charts + color-coded tables + sparklines |
| **B2** | B1 | prs-tracker 1.7-1.30 | PRS API endpoints + page routes + admin CRUD + data quality + core charts |
| **C1** | A1 | data-viz 6.1-6.20 | School routes, controllers, list/detail pages, restraint charts |

**Gate**: Visit `/data/discipline` → stacked bar chart renders. Visit `/prs` → paginated case list. Visit `/schools/00160005` → school detail with restraint chart. Vision check all three pages.

### Wave 2: Dashboards (parallel)

A2 and C1 complete unlock these.

| Task | Depends | Plan Ref | Output |
|------|---------|----------|--------|
| **A3** | A2 | data-viz 3.1-3.20 | Statewide dashboard, correlation views, YoY tracking, rankings |
| **B3** | B2 | prs-tracker 2.1-2.30 | PRS case detail, interactive timeline, deadline tracking, finding analysis |
| **C2** | C1 | data-viz 7.1-7.20 | School comparison, rankings, equity analysis, trends |

**Gate**: Visit `/data/dashboard` → stat cards + 2×2 chart grid. Visit `/prs/PRS-2024-0016` → timeline with colored dots, deadline countdown. Vision check both.

### Wave 3: District & County (sequential within, parallel across)

A3 unlocks D1. B3 unlocks E1.

| Task | Depends | Plan Ref | Output |
|------|---------|----------|--------|
| **D1** | A3 | data-viz 4.1-4.30 | District sparklines, badges, detail mini-charts, county mapping + views, radar compare |
| **E1** | B3 | prs-tracker 3.1-3.30 | PRS filing trends, resolution analysis, status/outcome, district rankings, YoY deep dives |
| **C3** | C2 | data-viz 8.1-8.20 | School-district relationship views, aggregate metrics, data integrity |

**Gate**: Visit `/districts/00160000` → hero bar, mini-charts, SPED outcomes, county label. Visit `/prs/analytics` → full dashboard with 6 charts. Vision check both.

### Wave 4: PRS Cross-Reference + School Advanced (parallel)

D1 and E1 unlock F1. C3 unlocks G1.

| Task | Depends | Plan Ref | Output |
|------|---------|----------|--------|
| **F1** | D1, E1 | prs-tracker 5.1-5.30 | PRS-vs-restraint correlation, cross-ref dashboards, combined views, district PRS tabs |
| **G1** | C3 | data-viz 9.1-9.20 | School explorer, time-series animation, report cards, data stories |

**Gate**: Visit `/data/combined` → PRS + DESE side-by-side. Visit `/schools/explore` → scatter plot with selectable axes. Vision check both.

### Wave 5: SVG Map (sequential)

The SVG map must be built before PRS heatmap. D1 provides district data; A1 provides Chart component.

| Task | Depends | Plan Ref | Output |
|------|---------|----------|--------|
| **H1** | A1 | data-viz 11.1-11.10 | SVG map source, embedding, responsive scaling, dark theme, county overlay |
| **H2** | H1, D1 | data-viz 12.1-12.10 | Data-to-map binding, color scales, no-data handling, multi-year preload |
| **H3** | H2 | data-viz 13.1-13.10 | Color scales (sequential, diverging, categorical, bivariate), legend component |
| **H4** | H3 | data-viz 14.1-14.10 | Interactive features: hover tooltip, click navigation, zoom/pan, search, keyboard nav |
| **H5** | H4 | data-viz 15.1-15.10 | Multi-metric views: dual map, difference map, small multiples, metric presets, embed |
| **H6** | H5 | data-viz 16.1-16.10 | Temporal animation: year slider, auto-play, transitions, delta overlay, export GIF |
| **H7** | H6 | data-viz 17.1-17.10 | Drill-down: slide-out panel, school overlay, county toggle, lasso select, table sync |
| **H8** | H7 | data-viz 18.1-18.10 | Comparison + export: side-by-side maps, PNG/SVG/PDF/GIF export, batch export |
| **H9** | H8 | data-viz 19.1-19.10 | Performance: path simplification, virtualization, caching, mobile, cross-browser |
| **H10** | H9 | data-viz 20.1-20.10 | Integration: route, embed, admin config, CSP, homepage embedding, documentation |

**Gate each**: After each H-step, visit `/data/map` → map renders with correct data. Vision check: Gemini Flash verifies colors, legend, tooltips, zoom behavior.

### Wave 6: PRS Map + Remaining PRS (parallel with late map phases)

Once H3 (color scales + legend) is done, PRS map can start. Once H7 (drill-down) is done, PRS map interaction can start.

| Task | Depends | Plan Ref | Output |
|------|---------|----------|--------|
| **I1** | H3, F1 | prs-tracker 4.1-4.18 | PRS map data API, density/heatmap/speed/risk maps, hotspots, interaction |
| **I2** | I1, H7 | prs-tracker 4.19-4.30 | County PRS maps, regional views, map export/embed |
| **J1** | F1 | prs-tracker 6.1-6.30 | PRS document upload/linking, finding analysis, OCR, document analytics |
| **K1** | F1 | prs-tracker 7.1-7.30 | PRS advanced viz: Sankey, waterfall, calendar heatmap, narrative, explorer |

**Gate**: Visit `/prs/map` → SVG map colored by PRS filing rate. Visit `/prs/PRS-2024-0016` → documents panel. Vision check both.

### Wave 7: Polish & Production (parallel)

| Task | Depends | Plan Ref | Output |
|------|---------|----------|--------|
| **A4** | D1, H10 | data-viz 5.1-5.20 | CSV export, print styles, accessibility, CSP, smoke test, docs |
| **C4** | G1 | data-viz 10.1-10.20 | School export, caching, mobile, accessibility, sitemap, analytics |
| **L1** | J1, K1 | prs-tracker 8.1-8.30 | PRS rankings, peer groups, temporal rankings, composite scoring |
| **M1** | L1 | prs-tracker 9.1-9.30 | PRS export, alerts, automation, RSS, webhooks |
| **N1** | M1 | prs-tracker 10.1-10.30 | PRS production: caching, mobile, accessibility, security, nav integration |

**Final gate**: All 30 phases verified. Full smoke test: 50+ routes, all charts, maps, timelines. Vision check: Gemini Flash reviews 10 key pages (homepage, data dashboard, district detail, school detail, PRS list, PRS detail, PRS analytics, PRS map, compare tool, rankings).

## Vision Verification Protocol

After every visual deliverable in every wave, run this check:

1. **Capture** — `browser.open` → navigate to page → `tab.screenshot()`
2. **Analyze** — `inspect_image` with Gemini Flash (`openrouter/google/gemini-2.5-flash`), prompt: "Verify this page matches a dark-theme data dashboard for Parent Data Force. Check: (1) correct brand colors — orange accents #ff5a1f, dark backgrounds #0b0b0b, (2) no broken CSS layouts or overlapping elements, (3) data values visible and correctly formatted with commas, (4) all chart axes labeled and legends readable, (5) navigation bar present with correct links. Report PASS/FAIL per check with specific issues."
3. **Fix** — if FAIL on any check, fix the issue before advancing to next subphase
4. **Log** — record vision check results in a running `local://vision-log.md` with timestamp, page, result, screenshot reference

Vision checks are required at these specific gates:
- After Wave 0: test chart page
- After Wave 1: `/data/discipline`, `/prs`, `/schools/{code}`
- After Wave 2: `/data/dashboard`, `/prs/{number}`
- After Wave 3: `/districts/{code}`, `/prs/analytics`
- After Wave 4: `/data/combined`, `/schools/explore`
- After Wave 5: `/data/map` (after each H-step H4-H10)
- After Wave 6: `/prs/map`, PRS case detail with documents
- After Wave 7: final 10-page sweep

## Dependency Graph

```
Wave 0:  A1 ─────────────────────┐  B1 ────────────┐
          │                       │                 │
Wave 1:  A2 ───────┐  C1 ───┐    │  B2 ───────┐    │
          │         │        │    │            │     │
Wave 2:  A3 ───┐   │   C2 ──┤    │  B3 ───┐   │     │
          │     │   │        │    │        │   │     │
Wave 3:  D1    │   │   C3 ──┤    E1       │   │     │
          │     │   │        │    │        │   │     │
Wave 4:  ├──F1─┤   │   G1   │    │        │   │     │
          │     │   │        │    │        │   │     │
Wave 5:  H1→H10 (sequential chain, depends on A1 + D1)
          │
Wave 6:  I1→I2 (depends H3+H7+F1)  J1 (depends F1)  K1 (depends F1)
          │
Wave 7:  A4  C4  L1→M1→N1 (all parallel)
```

## Execution Order Summary

| Wave | Tasks | Parallel? | Estimated subphases |
|------|-------|-----------|---------------------|
| 0 | A1, B1 | Yes (2 parallel) | 20 + 6 = 26 |
| 1 | A2, B2, C1 | Yes (3 parallel) | 20 + 24 + 20 = 64 |
| 2 | A3, B3, C2 | Yes (3 parallel) | 20 + 30 + 20 = 70 |
| 3 | D1, E1, C3 | Yes (3 parallel) | 30 + 30 + 20 = 80 |
| 4 | F1, G1 | Yes (2 parallel) | 30 + 20 = 50 |
| 5 | H1-H10 | Sequential | 10 × 10 = 100 |
| 6 | I1, I2, J1, K1 | Yes (4 parallel after H3/H7) | 18 + 12 + 30 + 30 = 90 |
| 7 | A4, C4, L1, M1, N1 | Yes (5 parallel) | 20 + 20 + 30 + 30 + 30 = 130 |
| **Total** | | | **610** |

## Critical files & anchors

| File | Why |
|------|-----|
| `dev/app/Components/Chart.php` | Gates everything visual. Must exist before any chart renders. |
| `dev/backend/schema.sql` | PRS tables appended here. Single source of truth for all DB schema. |
| `dev/app/Controllers/ApiController.php` | All chart data endpoints. Extend with 20+ new `case` branches across both plans. |
| `dev/public/assets/images/ma-districts.svg` | SVG map. Required for all map phases. External dependency — acquire before Wave 5. |
| `local://vision-log.md` | Running log of all Gemini Flash vision checks. Created during Wave 0, appended through Wave 7. |

## Verification

**End-to-end smoke test**: after Wave 7, visit all 50+ routes in sequence, capture HTTP status codes. All must return 200 (or 302/404 as expected). Then run the 10-page vision sweep with Gemini Flash. All pages must pass all 5 checks.

**Data integrity**: after each wave, run `SELECT COUNT(*)` on all new tables. Verify row counts match expected values from migration.

**Performance**: after Wave 7, run the chart smoke test page with 30+ charts. Total render time must be < 3s. Map page load must be < 2s with warm cache.

## Assumptions & contingencies

- Gemini Flash via OpenRouter is available. If the API key or endpoint is unreachable, fall back to manual visual inspection using `inspect_image` with the default vision model, or skip vision checks and rely on structural verification (HTML assertions, HTTP status codes).
- The MA district SVG map source is acquired before Wave 5. If unavailable, use the county-level SVG (14 counties) as a simpler alternative — skip district-level map phases and adapt PRS map to county-level only.
- `Chart.php` component is built in Wave 0 and not significantly modified afterward. If later phases need new chart features, extend the component, do not fork.
- D3.js and Sankey plugin (for PRS workflow diagrams) are loaded from CDN. If CDN is unavailable, degrade to simpler Chart.js bar charts for workflow visualization.
- All three plans reference the same codebase (`dev/`). No file conflicts between plans — each plan creates distinct files and extends existing ones via clearly named methods and routes.
