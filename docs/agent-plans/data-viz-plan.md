# Data Visualization Implementation — 20 Phases × 340+ Subphases

## Context

Platform has 30K+ DESE records across 6 datasets with only two bar charts. This plan delivers 100 subphases of visualization: every dataset gets multiple chart types, cross-dataset correlations, year-over-year trend highlighting, district-level drill-down, color-coded performance indicators, interactive tooltips/filters/toggles, and full export/print/accessibility support. All built on Chart.js 4.4.0 (already loaded via CDN) with a reusable PHP `Chart` component.

## Approach

### Phase 1: Chart Infrastructure & Design System (20 subphases)

**1.1 — Chart component class** — Create `dev/app/Components/Chart.php` with constructor `Chart(string $id, string $type)` where type ∈ {bar, line, pie, doughnut, radar, polarArea, scatter, bubble}. Stores datasets array, labels array, options array, height int. Methods: `addDataset(label, data, style)`, `setLabels(array)`, `setOption(key, value)`, `render()` outputs `<canvas>` + `<script>new Chart(...)</script>`.

**1.2 — Default dark-theme config** — `Chart::render()` merges user options over a default config: grid color `rgba(42,42,42,0.5)`, tick color `#767676`, font family `Inter`, font size 11, legend label color `#a0a0a0`. Tooltip background `#1d1d1d`, tooltip border `#2a2a2a`. All overridable per instance.

**1.3 — Color palette system** — Define `Chart::$palettes` array: `'default'` → `['#ff5a1f','#ffa366','#22c55e','#f59e0b','#ef4444','#60a5fa','#a78bfa','#ec4899']`, `'sentiment'` → `['#ef4444','#f59e0b','#22c55e']` (bad→good), `'cool'` → blues, `'warm'` → oranges. Each `addDataset()` call accepts `palette` key or defaults to index in default palette.

**1.4 — Responsive breakpoints** — `Chart::$responsive` config: at `<768px` reduce font to 9px, hide legend on pie/doughnut, switch bar to horizontal. At `<480px` hide y-axis labels, reduce point radius. Stored as Chart.js responsive options in the component.

**1.5 — Animation defaults** — All charts use `animation: { duration: 800, easing: 'easeOutQuart' }`. Bar charts add `animateScale: true` for vertical grow-in. Line charts use `tension: 0.3` for smooth curves. Users can disable via `setOption('animation', false)`.

**1.6 — Chart registry (prevent duplicate IDs)** — `Chart::$registry` static array. Constructor throws if ID already used on this page. `render()` adds ID to registry. Prevents invisible canvas-overwrite bugs.

**1.7 — Async data loading helper** — `Chart::renderAsync(string $apiUrl)` variant: outputs canvas + JS fetch wrapper that calls the API, parses JSON, calls `new Chart()`. Shows skeleton shimmer (CSS animation on canvas container) during load. Falls back to empty state text if fetch fails.

**1.8 — Tooltip custom formatter** — Default tooltip callback in `Chart::render()`: formats numbers with commas, appends units from dataset metadata (`unit: '%'`, `unit: ' students'`). Multi-line tooltip shows all datasets at that point, sorted by value desc.

**1.9 — Crosshair plugin** — Add Chart.js plugin in `app.js`: on mousemove, draw vertical line across all charts on page at cursor x. Only active when `crosshair: true` in chart options. Helps compare values across stacked charts on dashboard.

**1.10 — Click-to-filter interaction** — `Chart::render()` adds `onClick` handler: clicking a bar/slice calls `window.dispatchEvent(new CustomEvent('chart-filter', { detail: { label, value, dataset } }))`. Other page elements listen and filter tables/cards accordingly. Enables cross-chart drill-down.

**1.11 — API endpoint: restraint-trends** — `ApiController::data()` type `restraint-trends` already exists. Add `&district=` param to filter by org_code (aggregates schools). Add `&compare=` param with comma-separated org_codes for multi-line comparison. Returns `[{school_year, restraints, injuries, enrollment, rate_per_100}]`.

**1.12 — API endpoint: discipline-breakdown** — New type `discipline-breakdown`. Returns `[{school_year, in_school_susp, out_school_susp, expulsion, alt_setting, emergency_removal, arrest, law_enforce}]`. Accepts `&district=` filter. Grouped by school_year, sorted asc.

**1.13 — API endpoint: enrollment-demographics** — New type `enrollment-demographics`. Returns `[{school_year, sped_pct, el_pct, low_income_pct, high_needs_pct}]`. Accepts `&district=`. Each row is one year, columns are demographic groups.

**1.14 — API endpoint: attendance-trends** — New type `attendance-trends`. Returns `[{school_year, attendance_rate, chronic_absent_10, chronic_absent_20, avg_absences}]`. Accepts `&district=`.

**1.15 — API endpoint: sped-outcomes** — New type `sped-outcomes`. Returns `[{school_year, grad_rate, dropout_rate, inclusion_pct, parent_involve_pct, post_school_pct}]`. Accepts `&district=`.

**1.16 — API endpoint: prs-categories** — New type `prs-categories`. Returns `[{category, count}]`. Optional `&status=` filter (open/closed), `&year=` filter. Used for pie/doughnut charts.

**1.17 — API endpoint: district-comparison** — Already exists, extend. Returns `[{org_code, org_name, total_restraints, enrollment, rate_per_100, injuries}]`. Add `&metrics=restraint,discipline,attendance` to return multi-metric comparison. Each metric becomes a radar axis.

**1.18 — API endpoint: year-over-year** — New type `year-over-year`. Accepts `&metric=restraint_rate|discipline_rate|attendance_rate|sped_grad` and `&district=`. Returns `[{school_year, value, change_pct, change_abs, direction}]` where direction ∈ {up, down, flat}. Pre-computed change from previous year.

**1.19 — API response caching** — Wrap all chart endpoints in `Database::fetchAllCached($sql, $params, 300)`. TTL 5 minutes. Cache key includes all query params so filtered requests get separate cache entries. Add `Cache-Control: public, max-age=300` header to API responses.

**1.20 — Error & empty-state handling** — Every API endpoint: if DB error → `{"error": "message"}` with HTTP 500. If no rows → `[]` with HTTP 200. `Chart::renderAsync()`: on HTTP error shows "Data unavailable" text in chart area. On empty array shows "No data for this selection".

---

### Phase 2: Single-Dataset Deep Dives (20 subphases)

#### Restraint (4)

**2.1 — Multi-year restraint bar chart** — Replace homepage inline script with `Chart` component. Type `bar`. Dataset 1: `total_restraints` (accent orange bars). Dataset 2: `total_injuries` (warning yellow line, y1 axis). Already exists; rewrite to use component.

**2.2 — Restraint rate heat-map table** — On `/data/restraint`, add a toggle between raw table and color-coded table. Each cell's background: green ≤ state avg, yellow 1-2× avg, orange 2-3×, red ≥ 3×. State averages computed server-side and cached. CSS class per level applied to `<td>`.

**2.3 — Per-district restraint trend sparklines** — On `/data/restraint`, filter by district → small sparkline appears above table showing 9-year restraint trend for that district. Inline `<canvas width="200" height="40">`. No axes, just the line.

**2.4 — Restraint severity distribution** — New chart on `/data/restraint`: histogram of restraint counts. Buckets: 0, 1-5, 6-20, 21-50, 51-100, 100+. Bar chart showing how many schools fall in each bucket for the selected year. API computes on-the-fly via CASE WHEN in SQL.

#### Discipline (4)

**2.5 — Stacked discipline bar chart** — On `/data/discipline`, above the table. Type `bar` stacked. 6 datasets: in_school_susp, out_school_susp, expulsion, alt_setting, emergency_removal, arrest. X-axis: school years. Colors from cool palette.

**2.6 — Discipline rate vs enrollment scatter** — New chart on `/data/discipline`: scatter plot. X = enrollment, Y = discipline rate %. Each point = one district. Hover shows district name and values. Highlights correlation between district size and discipline rate. Uses Chart.js scatter type.

**2.7 — Discipline type proportions (latest year)** — Doughnut chart showing % breakdown of discipline types for the most recent year. Center text: total students disciplined. Uses `Chart.js` doughnut with `plugins.tooltip.callbacks` for percentage display.

**2.8 — Discipline disparity index** — New computed metric: ratio of (SPED discipline rate / general ed discipline rate). Displayed as a horizontal bar chart showing top 10 districts with highest disparity. Red bars with intensity proportional to ratio. API computes via subquery joining discipline_data to enrollment_data.

#### Enrollment (4)

**2.9 — Enrollment demographics doughnut** — On `/data/enrollment`, doughnut chart showing latest year's SPED/EL/Low Income/High Needs breakdown. 4 colored segments + center text with total students.

**2.10 — Enrollment trend lines** — Multi-line chart: 4 lines (SPED%, EL%, Low Income%, High Needs%) over all school years. X = year, Y = percentage. Each line a different color. Reveals demographic shifts over time.

**2.11 — District enrollment profile card** — When filtering `/data/enrollment` by district, show a "profile card" above the table: 4 horizontal bar gauges (SPED, EL, Low Income, High Needs) with state average markers as vertical reference lines. Color: green if below state avg, orange if above.

**2.12 — High-needs concentration map** — Horizontal bar chart: top 15 districts by high_needs_pct. X = %, Y = district name. Sorted descending. Color gradient from yellow (50%) to red (90%+). Shows where needs are concentrated.

#### Attendance (4)

**2.13 — Attendance rate trend line** — On `/data/attendance`, line chart: X = school year, Y = attendance rate %. Green line with point markers. Target line at 95% (dashed, grey). Highlights decline/recovery patterns.

**2.14 — Chronic absenteeism dual-axis** — Same chart as 2.13 but with second y-axis (right): chronic absenteeism %. Orange line. Inverse correlation visually apparent — as attendance drops, chronic absence rises.

**2.15 — Attendance rate ranking** — Horizontal bar chart: bottom 15 districts by attendance rate. X = %, Y = district name. Sorted ascending. Red-to-yellow gradient (low = red). Highlights at-risk districts.

**2.16 — Year-over-year attendance change** — Waterfall-style bar chart: each district's attendance rate change from previous year. Green bars = improvement, red bars = decline. X-axis = district names. Y-axis = percentage point change. Filters to selected year.

#### SPED + PRS (4)

**2.17 — SPED outcomes grouped bar** — On `/data/sped-results`, grouped bar chart: 3 bars per year (grad rate, dropout rate, inclusion %). Grad rate in green, dropout in red, inclusion in blue. Side-by-side comparison across years.

**2.18 — SPED vs general ed gap** — New derived chart: difference between SPED grad rate and overall grad rate per district. Horizontal diverging bar chart (left = SPED behind, right = SPED ahead). Red for negative gap, green for positive. Reveals equity gaps.

**2.19 — PRS complaint category pie** — On `/data/prs`, pie chart of complaint categories (top 8 + "Other"). Each slice a distinct color from default palette. Clicking a slice filters the table below to that category.

**2.20 — PRS status funnel** — Funnel-like horizontal bar chart: PRS intake statuses ordered by pipeline stage (open → investigating → findings → closed). Each bar's length = count. Color gradient from orange (open) to green (closed). Shows pipeline flow.

---

### Phase 3: Multi-Dataset Dashboards (20 subphases)

#### Statewide Overview (5)

**3.1 — Dashboard route & page** — New route `/data/dashboard` → `DataPortalController::dashboard()`. New view `dev/app/views/data-dashboard.php`. Full-width page with stat cards row at top, 2×2 chart grid below, ranking table at bottom.

**3.2 — Stat cards row** — 6 stat cards across top: Total Students, Total Restraints, Avg Attendance Rate, Avg SPED Grad Rate, Total PRS Complaints, Districts Reporting. Each card: large number, label below, small Δ indicator (green up arrow + %, red down arrow - %) showing year-over-year change from previous year.

**3.3 — Card click drill-down** — Clicking any stat card navigates to the corresponding data browser with filters pre-applied. Example: click "Total Restraints" → `/data/restraint?school_year=2023-24`.

**3.4 — 2×2 chart grid** — Top-left: Restraint trends (bar + line). Top-right: Discipline breakdown (stacked bar). Bottom-left: Enrollment demographics (doughnut). Bottom-right: Attendance vs SPED outcomes (dual-line). All charts use `Chart::renderAsync()` for independent loading.

**3.5 — Dashboard auto-refresh toggle** — "Auto-refresh: OFF | 5min | 15min" selector. Uses `setInterval` to re-fetch chart data. Default OFF. Useful for wall-mounted displays.

#### Correlation Views (5)

**3.6 — Restraint vs discipline scatter** — New chart: X = restraint rate per 100, Y = discipline rate %. Each point = one district. Trend line via Chart.js `plugin: { annotation: { type: 'line' } }`. Hover shows district name. Reveals districts that restrain AND discipline heavily.

**3.7 — Poverty vs outcomes correlation matrix** — Small multiples grid: 4 scatter plots arranged 2×2. Low_income_pct vs restraint rate, low_income_pct vs discipline rate, low_income_pct vs attendance rate, low_income_pct vs SPED grad rate. Each plot shows correlation coefficient (r value) in corner, computed server-side.

**3.8 — Enrollment vs restraint by SPED %** — Bubble chart. X = enrollment, Y = restraint rate, bubble radius = SPED %. Color = low_income_pct (gradient). Hover shows all 4 values. Reveals multi-dimensional patterns.

**3.9 — Year-over-year correlation change** — Animated chart: slider or play button cycles through school years, showing how the restraint-vs-poverty correlation changes over time. Each frame is a scatter plot for one year. Trend line moves. Correlation coefficient updates.

**3.10 — Data story annotations** — On correlation charts, add text annotations (Chart.js annotation plugin via CDN): arrows pointing to outlier districts, text boxes with "Highest restraint rate" or "Largest SPED gap". Annotations are hardcoded from SQL queries that find extremes.

#### Year-over-Year Tracking (5)

**3.11 — YoY change indicator component** — New PHP function `yoy_badge(float $current, float $previous): string`. Returns HTML span with arrow (↑/↓), percentage change, and color class (green for improvement, red for decline, grey for < 1% change). Used on stat cards and data tables.

**3.12 — YoY trend table** — On `/data/dashboard#trends`, a table showing every district with columns: Name, Restraint Rate (current), Restraint Rate (prior), Δ%, Discipline Rate (current), Discipline Rate (prior), Δ%, Attendance (current), Attendance (prior), Δ%. Sortable by any Δ% column. Color-coded cells.

**3.13 — Sparkline trend column** — In the YoY table, replace the numeric columns with inline sparklines (mini line charts 100×30px) showing 5-year trend. Clicking expands to full chart.

**3.14 — "Biggest movers" highlight** — Top section of YoY page: cards showing "Most Improved" and "Most Declined" districts across each metric. Computed server-side via LAG() window function or PHP array diff.

**3.15 — Historical context annotations** — On all trend charts, add vertical dashed lines at key events: COVID-19 (2019-20, 2020-21 school years) with label. DESE policy changes with label. Stored as a config array in PHP, injected as Chart.js annotation plugin config.

#### Rankings & Leaderboards (5)

**3.16 — Top/bottom 10 ranking table** — On `/data/dashboard#rankings`, tabbed interface: "Highest Restraint", "Lowest Attendance", "Highest SPED Gap", "Most PRS Complaints". Each tab shows a top-10 and bottom-10 table. Server-side query with ORDER BY + LIMIT 10.

**3.17 — Ranking change arrows** — Next to each district in ranking tables: ▲2 (moved up 2 spots) or ▼3 (moved down 3) since last year. Computed by comparing ranked positions between current and prior year arrays.

**3.18 — District comparison quick-select** — On ranking tables, checkbox column. Select 2-5 districts, click "Compare Selected" → redirects to `/compare?districts[]=...` with pre-filled selection. Enables rapid drill-down from rankings.

**3.19 — Performance badge system** — Each district gets computed badges shown on its card: "Low Restraint" (green), "High Attendance" (green), "Improving" (blue arrow), "Declining" (red arrow). Badges appear on district list cards and detail page. Computed from threshold rules in PHP config.

**3.20 — Export rankings to CSV** — Each ranking table has a "Download CSV" link. Calls `/api/data?type=rankings&metric=restraint&format=csv`. Returns `Content-Disposition: attachment`.

---

### Phase 4: District & County Visualizations (30 subphases)

#### District List (5)

**4.1 — District card sparklines** — Each district card on `/districts` gets a 120×35px sparkline showing 5-year restraint trend. `<canvas>` element with `data-values="[2019,2020,...]"` attribute. JS in `app.js` initializes tiny Chart.js line charts (no axes, no grid, 1px line width, orange color).

**4.2 — District card badges** — Each card shows 1-3 performance badges from 3.19. CSS badges positioned top-right of card. Badge icons: ✓ (good), ⚠ (warning), ✗ (poor). Tooltip on hover explains the badge.

**4.3 — District list sort controls** — Dropdown above the list: "Sort by: Name | Restraint Rate | Attendance Rate | SPED Grad Rate | Case Count". Changes SQL ORDER BY. Default: Name. Remembers choice via URL param `?sort=restraint_rate`.

**4.4 — District list filter chips** — Row of clickable chips below sort: "All Districts", "High Restraint", "Low Attendance", "Active Cases", "SPED Gap > 10%". Each chip adds a WHERE clause to the list query. Multiple chips combine with AND. Active chip highlighted orange.

**4.5 — District search-as-you-type** — Search input at top of district list. As user types ≥ 2 chars, JS fetches `/api/search?q=...&type=district` and shows dropdown of matching districts. Clicking navigates to that district page. Uses existing search API with added `type` filter.

#### District Detail (5)

**4.6 — District hero stats bar** — Top of detail page: 5 horizontal stat pills in a row. Org code, Town, Grade span, Total schools (count from organizations WHERE parent_org_id), Total enrollment (SUM from enrollment_data). Dark background, large numbers, compact layout.

**4.7 — District restraint trend mini-chart** — Replace text-based restraint panel with a 300×200px bar chart: 5 years of restraint totals. Orange bars. Value labels on top of each bar. Animated on page load.

**4.8 — District demographics gauge set** — Replace text-based demographics panel with 4 horizontal gauge bars: SPED %, EL %, Low Income %, High Needs %. Each gauge: colored bar (green→yellow→red gradient based on state percentile), state average marker (vertical grey line), value label on right. Width proportional to %.

**4.9 — District SPED outcomes comparison** — Replace SPED panel with grouped bar chart: 3 bars (grad rate, dropout rate, inclusion) for THIS district, with translucent background bars showing state average behind each. Color: district bar = orange, state avg bar = grey. Reveals district performance relative to state.

**4.10 — District timeline component** — New section at bottom: chronological timeline of cases + documents + updates for this district. Each entry = a dot on a vertical line. Color-coded: case = orange, document = blue, update = grey. Clicking opens detail. Data from cases + documents joined on org_id.

#### District Comparison (5)

**4.11 — Radar comparison chart** — On `/compare`, below the data table: radar (spider) chart. Up to 5 districts, 5 axes: Restraint Rate, Discipline Rate, Attendance Rate, SPED Grad Rate, Chronic Absenteeism (inverted). Each district a different colored polygon. Hover highlights one district, dims others. Legend clickable to toggle districts.

**4.12 — Side-by-side bar comparison** — Alternative to radar: grouped bar chart. X-axis = metrics, grouped bars per district. Easier to read exact values than radar. Toggle between radar and grouped bar via buttons.

**4.13 — Comparison summary card** — Above the chart: "Best performer: [District] for Restraint Rate, [District] for Attendance..." — auto-generated text highlighting which selected district leads each metric. Computed by finding min/max in the comparison data array.

**4.14 — Historical comparison lines** — Instead of single-year comparison: select a metric, chart shows 5-year line for each selected district. X = school year, Y = metric value. Multiple colored lines. Reveals if gaps are widening or narrowing over time.

**4.15 — Comparison export** — "Download Comparison Report" button: generates a PDF-like HTML page (print-optimized) with the radar chart, bar chart, data table, and summary. Opens in new tab. Uses `window.print()` for PDF export. No server-side PDF generation needed.

#### District Discovery (5)

**4.16 — "Similar districts" recommendation** — On district detail page, a sidebar/panel: "Similar Districts" showing 3-5 districts with similar enrollment, demographics, and grade span. Computed by Euclidean distance across normalized metrics in SQL. Links to those district pages.

**4.17 — District heat map (MA map)** — Leaflet.js or simple SVG map of Massachusetts with districts colored by selected metric (restraint rate, attendance, etc.). Color scale: green (good) → yellow → orange → red (bad). Clicking a district navigates to its page. _Note: requires MA district boundary GeoJSON — use simplified topojson from census.gov._

**4.18 — District metric trends at a glance** — On district detail: a "Metric Trends" grid of 6 tiny sparklines (2 rows × 3 cols): Restraint, Discipline, Attendance, Enrollment, SPED Grad, SPED Dropout. Each sparkline 100×50px, 5-year trend. Color: green if improving, red if worsening. Clicking any sparkline scrolls to the full chart for that metric.

**4.19 — District peer percentile** — Every metric on district detail shows "XXth percentile (vs all districts)". Computed server-side by counting districts with better/worse values. Displayed as small text below each stat value. Example: "63.0% grad rate — 42nd percentile (below average)".

**4.20 — District "at a glance" printable summary** — "Print Summary" button on district detail: generates a single-page printable view with district header, key stats, top 3 charts, and contact info. Uses `@media print` CSS. No server-side PDF — just browser print.

#### County Comparison (5)

**4.21 — County mapping table** — New table `counties` (id, county_name, slug) seeded with 14 MA counties. New column `county_id INT UNSIGNED NULL` added to `organizations` with FK. Populated via town→county lookup using a hardcoded PHP mapping array (`'Attleboro' => 'Bristol', 'Boston' => 'Suffolk', ...`). Migration script updates all organizations. County appears on district/school detail pages.

**4.22 — County aggregation API** — New API type `county-comparison`. Accepts `&metric=restraint_rate|discipline_rate|attendance_rate|sped_grad|chronic_absent`. Returns `[{county_name, value, rank, state_avg, above_avg}]`. Aggregates DESE data via organizations.county_id JOIN. All numeric data summed/averaged per county per school year. Accepts `&school_year=` filter.

**4.23 — County comparison bar chart** — On `/compare` (or new `/counties` route): horizontal bar chart. X = metric value, Y = county names sorted by value. Each bar colored: green if better than state avg, red if worse. State average line (dashed grey) as vertical reference. Dropdown switches metric. Clicking a county bar drills into county detail (4.25).

**4.24 — County rankings leaderboard** — New section on `/data/dashboard#counties`: sortable table with columns County, # Districts, Total Enrollment, Restraint Rate, Discipline Rate, Attendance Rate, SPED Grad Rate. Each cell color-coded (green = top quartile, red = bottom quartile). "Best/Worst" toggle filter. County names linked to county detail.

**4.25 — County detail page** — New route `/counties/{slug}` → `CountyController::show()`. Shows: county name + map outline (SVG placeholder), stat cards (districts count, schools count, total enrollment), 4 charts (restraint trend, discipline breakdown, attendance trend, SPED outcomes — all aggregated from constituent districts), district list within county (sortable table), "Compare to state average" overlay on each chart.

**4.26 — County map visualization** — Replace/supplement district heat map (4.17) with county-level choropleth. SVG map of MA counties colored by selected metric. Color scale: 5-step gradient green→red. Hover shows county name + value. Click navigates to county detail. Uses a static MA counties SVG (no external dependency — embed inline).

**4.27 — County vs county pairwise comparison** — On county detail: "Compare with another county" dropdown. Selecting a second county adds overlay line/bar for that county on all charts. Both counties' data shown side-by-side. "Clear comparison" button resets to single-county view.

**4.28 — County year-over-year trends** — County detail charts emphasize YoY change: line charts show all years, bar charts have previous year as translucent overlay bar behind current year. YoY badge (3.11) applied to county stat cards. "Biggest county movers" section on dashboard (3.14 extended).

**4.29 — County equity lens** — New derived metrics computed at county level: SPED restraint disparity ratio, low-income discipline disparity ratio. Displayed as diverging bar charts (left = worse than state, right = better). Highlights which counties have largest equity gaps.

**4.30 — County data export** — "Download county report" on county detail: CSV of all aggregated metrics across all years for that county. "Download all counties" on county rankings: single CSV with all 14 counties × all metrics × latest year. Uses existing CSV export infrastructure (5.2).

---

### Phase 5: Export, Polish & Production (20 subphases)

#### Export System (5)

**5.1 — CSV export for all API endpoints** — `ApiController::data()` accepts `&format=csv`. Returns `Content-Type: text/csv` with `Content-Disposition: attachment; filename="dataset-name.csv"`. Headers from SQL column aliases. All numeric values unformatted. All 7 chart endpoints support this.

**5.2 — Excel-compatible CSV** — CSV output uses `,` separator, `"` enclosure for strings, `\r\n` line endings. UTF-8 BOM prefix for Excel compatibility. All via a new `array_to_csv(array $rows): string` helper in `dev/app/Core/helpers.php`.

**5.3 — Chart image export** — "Download Chart as PNG" button below every chart. Uses Chart.js `toBase64Image()` method, creates a temporary `<a download>` element, triggers click. Client-side only. No server round-trip.

**5.4 — Bulk data export page** — New route `/data/export`. Simple page with checkboxes for each dataset, year range selector (From/To), district selector, and format selector (CSV/JSON). "Download All" button generates and downloads. Multiple files zipped client-side via JSZip (loaded from CDN).

**5.5 — Email report subscription** — New route `/subscribe/report`. Form: email, frequency (monthly/quarterly), districts of interest, metrics of interest. Stores in new `report_subscriptions` table (id, email, frequency, districts JSON, metrics JSON, active, created_at). Placeholder: "Coming soon — email delivery infrastructure pending."

#### Print & Accessibility (5)

**5.6 — Print stylesheet** — New file `dev/public/assets/css/print.css` loaded via `<link media="print">` in `Layout.php`. Hides: nav, footer, buttons, filter bars, hero-stats, submit-tabs. Ensures charts `page-break-inside: avoid`. Sets body background white, text black. Font size 10pt for tables.

**5.7 — Print-optimized chart rendering** — In `Chart::render()`, detect `@media print` via JS: increase font sizes, add data labels to bars/slices, remove animations. Chart renders in final state for print capture.

**5.8 — Screen reader descriptions** — Every `Chart::render()` call generates a hidden `<div class="sr-only">` with a text summary: dataset name, chart type, key values, trend direction. Example: "Bar chart: Student Restraint Trends 2019-2024. Total restraints increased from 4,735 in 2016-17 to 9,070 in 2023-24, a 92% increase." Built from dataset + labels arrays.

**5.9 — Keyboard-navigable charts** — Chart.js canvas gets `tabindex="0"` and `role="img"`. Arrow keys cycle through data points. Enter announces value via aria-live region. Uses Chart.js built-in accessibility features + custom `onKeyDown` handler.

**5.10 — High-contrast mode** — Detect `prefers-contrast: high` via CSS media query. Swap chart colors to high-contrast palette (white bg, black text, thick borders, distinct patterns). Toggle manually via accessibility menu in footer.

#### Performance & Caching (5)

**5.11 — Chart data API caching** — All chart endpoints wrapped in `Database::fetchAllCached($sql, $params, 300)`. 5-minute TTL. Separate cache per query param combination. `Database::clearCache()` callable from admin panel.

**5.12 — CDN subresource integrity** — Chart.js `<script>` tag gets `integrity="sha384-..."` attribute. If CDN fails integrity check, fallback to local copy. SRI hash computed from current Chart.js 4.4.0 build.

**5.13 — Lazy-load below-fold charts** — Charts not in initial viewport use `IntersectionObserver` to defer loading until scrolled into view. `Chart::render()` wraps canvas in a div with `data-lazy-chart` attribute. JS in `app.js` handles observer.

**5.14 — Chart debounce on resize** — Chart.js resize handler debounced at 200ms. Prevents excessive re-renders during window resize. Added via Chart.js `resizeDelay` option in default config.

**5.15 — Admin cache management** — Admin dashboard section: "Data Cache" showing cache hit count, miss count, total entries. "Clear Cache" button calls `Database::clearCache()`. Audit log records cache clears.

#### Production Hardening (5)

**5.16 — CSP update for chart CDN** — Update `.htaccess` Content-Security-Policy to include `https://cdn.jsdelivr.net` in `script-src` and `https://*.tile.openstreetmap.org` in `img-src` (for future map tiles). Already partially configured — verify completeness.

**5.17 — Error boundary for chart JS** — Wrap every `new Chart()` call in try/catch. On error, hide canvas, show "Chart unavailable" text with the dataset name. Prevents a single broken chart from breaking page JS.

**5.18 — Chart performance smoke test** — Test page (dev only, not in production routes): `/dev/chart-test` renders all 30+ chart types on one page. Measures total render time via `performance.now()`. Target: < 3 seconds for all charts on a modern browser.

**5.19 — Mobile responsive verification** — Test every chart page at 375px, 768px, 1024px widths. Verify: no horizontal scroll, legends wrap or hide, touch targets ≥ 44px, charts fill container. Fix any overflow issues.

**5.20 — Documentation page** — New route `/data/help` or section in existing `/resources`: explains each chart type, what the data means, how to filter, how to export. Links to DESE data definitions. Static PHP page with screenshot images of each chart (generated once, stored in assets).


---

### Phase 6: School Data Infrastructure (20 subphases)

#### School Data Model (5)

**6.1 — School route and controller** — New route `/schools/{slug}` → `SchoolController::show()`. Slug = org_code lowercase. Controller queries `organizations WHERE org_code = ? AND org_type IN ('Public School','Charter School',...)`. 404 if not found. Passes school data + parent district data to view.

**6.2 — School list page** — New route `/schools` → `SchoolController::list()`. Paginated list of all 1,813 schools. Filterable by district (dropdown), grade span (PK-5, 6-8, 9-12), org_type. Each card shows: school name, district name, town, grade span, enrollment, restraint count (latest year). Sortable by name, enrollment, restraint rate.

**6.3 — School-to-district breadcrumb** — Every school page shows breadcrumb: Home → Districts → [District Name] → [School Name]. District name links to district detail. Computed from organizations.parent_org_id.

**6.4 — School search endpoint** — Extend `/api/search` with `&type=school`. Returns matching schools by name, org_code, or town. Used by school search-as-you-type and district detail "Schools in this district" list.

**6.5 — School data API endpoint** — New API type `school-profile`. Accepts `&org_code=`. Returns JSON: `{school: {name, code, type, town, grade_span}, district: {name, code}, restraint: [{school_year, enrollment, students_restrained, total_restraints, total_injuries}], demographics: {sped_pct, el_pct, low_income_pct} (from parent district) }`. Single endpoint serves the entire school detail page.

#### School Restraint Visualizations (5)

**6.6 — School restraint trend chart** — On school detail: bar chart of yearly restraint totals. Orange bars. Value labels on top. State average line (grey dashed) for comparison. 9 years shown (all available data). If data is suppressed by DESE (total_restraints_suppressed=1), bar is grey with "*" marker and tooltip "Data suppressed by DESE for privacy."

**6.7 — School restraint vs district comparison** — Dual-line chart: school restraint rate (orange line) vs district average restraint rate (grey line) over all years. X = school year, Y = rate per 100 students. Highlights whether the school is above/below its district. Fill area between lines: red if school > district, green if school < district.

**6.8 — School restraint percentile gauge** — Semi-circular gauge chart showing school's restraint rate percentile among all schools of same grade span. Green arc (0-50th), yellow (50-75th), orange (75-90th), red (90-100th). Needle points to school's position. Number below: "This school restrains more than XX% of similar schools" or "less than XX%". Uses Chart.js gauge plugin or custom canvas drawing.

**6.9 — School injury rate focus** — Small donut chart: restraint incidents resulting in injury vs no injury. Red segment = injuries. Center text = injury %. Below: "X of Y restraints resulted in injury." Highlights safety concern.

**6.10 — School restraint data table with suppression** — Below charts: table of all school-year rows. Columns: Year, Enrollment, Students Restrained, Total Restraints, Injuries, Rate/100, Suppressed. Suppressed rows have greyed-out cells with "Data withheld by DESE" tooltip. DESE suppression rules: rows with < 6 students restrained are suppressed.

#### School Enrollment and Demographics (5)

**6.11 — School enrollment trend** — Line chart: school enrollment over all available years. Blue line. District enrollment trend as grey comparison line (from district-level enrollment_data, divided by number of schools). Reveals if school is growing/shrinking relative to district.

**6.12 — School demographics from district context** — Information panel (not chart): "Demographic data is reported at the district level." Shows parent district's demographics (SPED %, EL %, Low Income %, High Needs %) with note "School-level demographics are not publicly reported by DESE. These figures represent the parent district." Uses enrollment_data for the parent district's org_id.

**6.13 — School grade span visualization** — Horizontal "grade ladder" showing which grades the school serves. Colored blocks for each grade (PK, K, 1-12). Filled = served by this school, greyed out = not served. Data from organizations.grade_span field. Visual indicator of school type (elementary, middle, high).

**6.14 — School peer group comparison** — "Schools like this one" section: finds all schools with same grade_span pattern and similar enrollment (±25%). Shows mini-table: top 5 peer schools by lowest restraint rate. "This school ranks #X of Y similar schools." Peer list links to each school's detail page.

**6.15 — School Title I status indicator** — Badge displayed if school has Title I status. "Title I Schoolwide" or "Title I Targeted Assistance" from organizations.title_1_status. Colored badge with tooltip explaining what Title I means. Relevant because Title I schools often have different resource levels.

#### School Navigation and Discovery (5)

**6.16 — Schools in this district panel** — On district detail page: expandable panel listing all schools with parent_org_id = this district. Each school row: name, grade span, enrollment, restraint rate, sparkline. Sorted by name. "View all X schools" link to filtered school list.

**6.17 — School search with filters** — On `/schools`: search bar with autocomplete (6.4), district dropdown filter, grade span checkboxes (Elementary PK-5, Middle 6-8, High 9-12), org_type checkboxes (Public, Charter, Vocational, etc.). Filters update results via page reload with query params.

**6.18 — Nearby schools** — On school detail: "Nearby Schools" showing 3-5 schools in the same town or adjacent towns. Computed by querying organizations with same town first, then same district. Distance not computed — just geographic proximity by town name. Links to each school.

**6.19 — School list sort options** — On `/schools`: sort dropdown with options: Name (default), Enrollment (high-low), Enrollment (low-high), Restraint Rate (high-low — "Most Restraints"), Restraint Rate (low-high — "Least Restraints"). URL param `?sort=` drives SQL ORDER BY.

**6.20 — School comparison quick-add** — On school detail and school list: checkbox or "Add to comparison" button. Selected schools stored in JS array (or URL params). "Compare Selected (N)" button appears when N ≥ 2. Clicking navigates to `/schools/compare?codes=...`. Works across different districts.

---

### Phase 7: School Comparison and Ranking (20 subphases)

#### School Comparison Tool (5)

**7.1 — School comparison page** — New route `/schools/compare` → `SchoolController::compare()`. Accepts `?codes=00160005,02070025,...`. Shows comparison table: rows = schools, columns = Name, District, Enrollment, Restraint Rate, Injury Rate, Grade Span, Title I Status. Each cell color-coded (green = best among selected, red = worst). Up to 5 schools compared at once.

**7.2 — School comparison bar charts** — Below comparison table: small multiple bar charts. One chart per metric (enrollment, restraint rate, injury rate). X = school names, Y = metric value. Each school same color across all charts. Arranged in a horizontal row. Easy visual scanning across metrics.

**7.3 — School comparison radar chart** — Radar chart for selected schools. Axes: Restraint Rate (inverted — lower = better), Enrollment, Grade Span Width (number of grades), Title I (1 or 0). Each school a different colored polygon. Highlights different school profiles at a glance.

**7.4 — School comparison summary card** — Auto-generated text above charts: "[School A] has the lowest restraint rate (X.X per 100) among selected schools. [School B] has the highest enrollment (X,XXX students)." Helps users quickly interpret the comparison without reading all numbers.

**7.5 — School comparison export** — "Download Comparison" button generates printable HTML page with all comparison charts + table. Uses `window.print()` for PDF. Same pattern as district comparison export (4.15).

#### School Rankings (5)

**7.6 — School rankings page** — New route `/schools/rankings` → `SchoolController::rankings()`. Tabbed interface: "Highest Restraint Rate", "Lowest Restraint Rate", "Largest Enrollment", "Most Injuries", "Most Improved" (biggest restraint rate decline YoY). Each tab = top 25 + bottom 25 table. Computed via SQL ORDER BY + LIMIT.

**7.7 — School rankings by grade span** — Each rankings tab has a grade-span filter: "All", "Elementary (PK-5)", "Middle (6-8)", "High (9-12)". Filters the ranking query. Different grade spans have different restraint profiles — filtering makes rankings fair.

**7.8 — School rankings year selector** — Dropdown on rankings page: select school year. Rankings computed for that year. Default: latest year. Allows historical comparison ("was this school always high-restraint?").

**7.9 — School ranking position badge** — On school detail page: "Ranked #X of Y [grade span] schools for [metric] in [school year]." Example: "Ranked #847 of 1,024 elementary schools for restraint rate in 2023-24 (bottom 17%)." Multiple badges for different metrics. Color: green if top quartile, red if bottom quartile.

**7.10 — School ranking movement tracker** — For each school in rankings: up/down indicator showing rank change from previous year. "Moved up 47 spots" or "Moved down 12 spots." "New to rankings" for schools with no prior year data. "Unchanged" for < 3 spots movement.

#### School Equity Analysis (5)

**7.11 — SPED restraint disparity at school level** — Computed metric: compare school restraint rate to district SPED %. Scatter plot: X = district SPED %, Y = school restraint rate. Color = school grade span. Each point = one school. Trend line shows correlation.

**7.12 — Title I vs non-Title I comparison** — Grouped bar chart: average restraint rate for Title I schools vs non-Title I schools, grouped by grade span. Reveals whether Title I schools (higher poverty) have systematically different restraint rates. Computed by JOIN organizations.title_1_status with restraint_data.

**7.13 — Charter vs traditional public comparison** — Grouped bar chart comparing charter schools vs traditional public schools on: restraint rate, enrollment size, grade span distribution. Computed by organizations.org_type filter. Each bar = one metric, grouped by school type.

**7.14 — Outlier detection highlight** — On school rankings and comparison: automatically flag statistical outliers (at least 2 standard deviations from mean). Outlier rows have a warning icon and red border. Tooltip: "This value is unusually high/low compared to similar schools." Computed in PHP after fetching ranking data.

**7.15 — Equity scorecard** — On school detail: an "Equity Scorecard" panel. Compares school to state averages on: restraint rate, injury rate, enrollment size. Green check if better than state avg, red X if worse, grey dash if within 10%. Simple visual at-a-glance equity assessment.

#### School Trends and History (5)

**7.16 — School multi-year trend dashboard** — On school detail: tab "5-Year Trends" showing small multiple charts for: enrollment, restraint total, restraint rate, injuries. All 4 charts share the same X-axis (school years) for easy scanning. Each chart a 200x150px mini line/bar chart.

**7.17 — School first restraint year indicator** — On school trend chart: highlight the first year restraints were reported. If school opened after 2016, note "School opened in [year]." If restraints started mid-timeline, annotation: "First reported restraints: [year]." Helps distinguish "no restraints" from "new school."

**7.18 — School closure/inactive detection** — If school has no data in the most recent 2 years and is_active=0: show "This school may be closed or inactive" banner. Prevents confusion when looking up closed schools.

**7.19 — School restraint policy change annotations** — If district had a documented policy change (from cases table or updates), annotate the school trend chart with a vertical line + label. Example: "District adopted PBIS framework (2021)." Stored in a new `school_events` table (id, org_id, event_date, event_type, description) manually populated by admin.

**7.20 — School data completeness indicator** — On school detail: bar showing "X of 9 years with data reported." Each year a small block — green if data exists, grey if no data/missing. Quick visual indicator of how much history is available. Some schools may be new or have spotty DESE reporting.

---

### Phase 8: School-District Relationship Views (20 subphases)

#### District-to-Schools Views (5)

**8.1 — District schools overview panel** — On district detail: "Schools in this District" section replacing the simple list (6.16). Shows: total schools count, grade span range, enrollment range. Table: school name, type, grades, enrollment, restraint rate (latest), sparkline. Sortable columns. "View all schools" link.

**8.2 — District schools scatter plot** — On district detail: scatter plot of all schools in the district. X = enrollment, Y = restraint rate. Each point = one school, labeled with school name on hover. Color = grade span (elementary=blue, middle=orange, high=green). District average as horizontal line. Quickly shows within-district variation.

**8.3 — District schools restraint distribution** — Histogram: restraint rates of schools within the district. X = rate buckets (0, 0.1-1, 1-5, 5-10, 10+), Y = number of schools. State average overlay line. Shows if district has a "long tail" of high-restraint schools or is uniformly distributed.

**8.4 — District school-to-school comparison** — On district detail: "Compare schools within this district" section. Two dropdowns to select schools. Shows side-by-side comparison: enrollment, restraint rate, restraint total, injury rate, grade span. Mini bar chart for each metric. Highlights which school performs better on each metric.

**8.5 — District school of concern flag** — District detail auto-flags schools with restraint rate > 2x state average. Shows a red-bordered card: "[School Name] — Restraint rate X.X per 100 (state avg: Y.Y)." Links to school detail. Admin-configurable threshold in system_config.

#### School-to-District Context (5)

**8.6 — School district percentile** — On school detail: for each metric, show where the school stands within its district. "Restraint rate: 12.7 per 100 — highest among all 8 schools in [District]." "Enrollment: 432 — 3rd largest of 8 schools." Computed by fetching all schools in parent district and ranking.

**8.7 — School contribution to district total** — On school detail: "This school accounts for X% of all restraints in [District]." Computed by SUM of school's restraints / SUM of all schools' restraints in district. Doughnut chart: school's share vs rest of district. Highlights outsized contributors.

**8.8 — District demographics on school page** — School detail shows parent district's demographic breakdown (from 6.12) with a note linking to district detail. "See full district profile" link. Contextualizes the school within its broader community.

**8.9 — District cases related to school** — On school detail: if the parent district has cases in the cases table, show "Related District Cases" panel. Lists case title, number, status. Links to case detail. "These cases involve the [District] and may relate to this school."

**8.10 — Cross-district school comparison** — "How does this school compare to schools in nearby districts?" Shows mini table: this school + 3 similar schools (same grade span, similar enrollment) from different districts. Restraint rate comparison. Links to each school. Highlights whether the issue is school-specific or district-wide.

#### Aggregate District Metrics from Schools (5)

**8.11 — District restraint rate computed from schools** — On district detail: note "District restraint rate (X.X per 100) is aggregated from all schools in the district with reported data." District-level chart uses SUM of school-level restraint_data grouped by parent_org_id. Clarifies data lineage.

**8.12 — District data completeness from schools** — On district detail: "X of Y schools in this district reported restraint data for [year]." Progress bar showing reporting completeness. Low reporting = possible data quality issue.

**8.13 — School-count trend for district** — Line chart: number of active schools in the district per year. X = school year, Y = count. Declining count may indicate closures/mergers. Data from organizations WHERE parent_org_id = X GROUP BY created_at year.

**8.14 — District school size distribution** — Horizontal bar chart on district detail: enrollment buckets (0-100, 101-300, 301-500, 501-1000, 1000+). Y = bucket, X = number of schools in bucket. Shows whether district has many small schools or few large ones. Each bar segmented by school type (elementary/middle/high).

**8.15 — District grade span coverage** — Visual grid: columns = grades PK-12, rows = schools. Filled cell = school serves that grade. Shows grade transitions (which schools feed into which). Helps parents understand the school pathway in the district.

#### School-District Data Integrity (5)

**8.16 — Orphan school detection** — Admin dashboard alert: schools with parent_org_id = NULL or parent_org_id pointing to non-existent organization. "X schools have no parent district." Links to admin organizations list filtered to orphans. Fixable in admin panel.

**8.17 — School-district enrollment reconciliation** — Comparison: SUM of school enrollments (from restraint_data) vs district enrollment (from enrollment_data). Displayed as: "School-reported enrollment: X,XXX. District-reported enrollment: X,XXX. Difference: plus or minus X%." Highlights DESE data discrepancies.

**8.18 — School name-to-code mapping verification** — On admin organizations list: flag schools where org_name doesn't follow expected pattern (e.g., doesn't contain the district name). "Possible naming inconsistency." Admin can edit org_name.

**8.19 — School grade span validation** — Detect schools with unusual grade spans: empty grade_span, spans containing non-numeric values, single-grade schools (rare — usually SPED schools). Flag in admin panel for review.

**8.20 — Automated data quality report** — New admin page `/admin/data-quality`: summary of all integrity checks (8.16-8.19). Counts of issues by type. "Run Report" button re-runs all checks. "Export Issues CSV" downloads flagged records. Audit log records report generation.

---


---

### Phase 9: School Advanced Visualizations (20 subphases)

#### Interactive School Explorer (5)

**9.1 — School data explorer** — New route `/schools/explore`. Interactive scatter plot: X-axis and Y-axis each selectable metric. Each point = one school. Color = grade span. Size = enrollment. Hover = school name and values. Brush-select to highlight. Click to navigate. All 1,813 schools plotted. Uses Chart.js scatter type.

**9.2 — Explorer metric selectors** — Two dropdowns above scatter plot: X-axis metric, Y-axis metric. Options: enrollment, restraint_rate, injury_rate, district_sped_pct, district_low_income_pct, title_1. Changing dropdowns re-fetches data via API and re-renders plot.

**9.3 — Explorer filters** — Sidebar filters: grade span checkboxes, district dropdown (multi-select), enrollment range slider, Title I toggle. Applying re-queries and updates plot. Filter count badge: "X of 1,813 schools shown."

**9.4 — Explorer trend line** — Toggle to show linear regression trend line. Computed client-side. R-squared value displayed. "Correlation: weak/moderate/strong positive/negative." Helps identify relationships between metrics.

**9.5 — Explorer saved views** — "Save this view" button stores metric selections and filters as a named bookmark in localStorage. "Load view" dropdown. Share via URL: all params encoded in query string.

#### School Time-Series Animation (5)

**9.6 — Animated scatter over time** — "Animate: 2016-17 through 2023-24" button. Each frame = one year. Points animate between positions. Play/pause/speed controls. Reveals school trajectories over time.

**9.7 — School trail paths** — Select one school to show its trail — a line connecting its position across all years. Other schools shown as grey dots. Trail shows the school's journey through the metric space.

**9.8 — Year-over-year change vectors** — Arrow plot: each school = one arrow from (previous year X, previous year Y) to (current year X, current year Y). Color: green if both improved, red if both worsened, orange if mixed. Length = magnitude of change.

**9.9 — School cohort tracking** — Group schools by first year of data. Line chart: average restraint rate per cohort over years since first data (not calendar year). Reveals if newer schools differ from older ones.

**9.10 — School bubble up highlights** — Auto-detect schools with significant YoY changes (over 2 sigma). List them below chart. Clicking zooms to that school. "Jump to next highlight" button cycles through them.

#### School Report Cards (5)

**9.11 — Printable school report card** — "Download Report Card" button on school detail. Single-page printable view: header, key stats, restraint chart, district comparison, peer comparison, equity scorecard, data completeness. Print-optimized CSS.

**9.12 — School report card PDF generation** — Server-side PDF via Dompdf or TCPDF. "Download PDF" generates styled report. Cached 24 hours. Same layout as printable version.

**9.13 — School report card email** — "Email this report" form: recipient email, optional message. Sends PDF attachment via PHP mail(). Rate-limited: 5/hour per IP.

**9.14 — Batch school report cards** — "Download Report Cards for All Schools in District." Multi-page PDF. "Download All Elementary Schools." Long-running: progress bar, email when ready.

**9.15 — Report card template customization** — Admin page: select sections, order, colors, logo size. Stored in system_config as JSON. Preview button. "Reset to default."

#### School Data Stories (5)

**9.16 — Automated school narrative** — Auto-generated 2-3 sentence narrative from data. Example: "[School] served 432 students in 2023-24 across grades 9-12. Restraint rate of 12.7 per 100 is above state average of 9.5. Restraints increased 34% since 2019-20." PHP template with data substitution.

**9.17 — Did you know factoids** — Sidebar on school detail: rotating facts. "Did you know? This school has reported restraints every year since 2016." "Enrollment peaked at 512 in 2019-20." Each factoid from SQL query finding interesting data points.

**9.18 — School comparison narrative** — When comparing 2+ schools: auto-generated paragraph summarizing key differences. "[School A] has lower restraint rate (3.2 vs 12.7) but higher enrollment (890 vs 432) than [School B]."

**9.19 — Data source citations** — Footer on every school page: "Data source: Massachusetts DESE Profiles. Restraint data may be suppressed for schools with fewer than 6 students. Last updated: [date]."

**9.20 — School page social sharing** — Open Graph tags on school detail. og:image = dynamically generated chart PNG or static fallback. Twitter card: summary_large_image.

---

### Phase 10: School Export, Polish and Production (20 subphases)

#### School Data Export (5)

**10.1 — School CSV export** — "Download CSV" on school list, detail, and rankings. Exports visible data respecting filters. Uses existing CSV infrastructure.

**10.2 — School bulk data download** — `/schools/export`: field checkboxes, year selector, format selector. "Download All Schools" exports all 1,813 with selected fields.

**10.3 — School data API documentation** — Section in `/data/help` documenting school API endpoints with example requests and responses.

**10.4 — School comparison export** — Export comparison data as CSV or printable report. Same pattern as district comparison export.

**10.5 — Scheduled school data email** — Subscribe to email updates for specific schools. Stores in report_subscriptions table. Placeholder for email infrastructure.

#### School Page Performance (5)

**10.6 — School detail page caching** — School queries wrapped in fetchAllCached with 300s TTL. Cache key includes org_code.

**10.7 — School list pagination optimization** — Cursor-based pagination beyond page 5. OFFSET for first 5 pages. Avoids scanning all previous rows.

**10.8 — School chart lazy loading** — Restraint trend loads immediately. Peer comparison, district comparison, and trend dashboard load via IntersectionObserver.

**10.9 — School image/logo system** — New column organizations.logo_url. Admin uploads logos. Fallback: generated SVG with school initials in colored circle.

**10.10 — School page weight budget** — Target: under 500KB total. If over: reduce chart data precision, defer non-critical charts, compress API responses.

#### School Accessibility and Mobile (5)

**10.11 — School page mobile layout** — Single-column at under 768px. Charts stack vertically. Breadcrumb collapses to back link. Touch targets at least 44px.

**10.12 — School chart screen reader summaries** — Every chart has sr-only description with school-specific text.

**10.13 — School data table keyboard nav** — Arrow keys navigate cells. Enter announces content. Tab between table and charts. role="grid" with aria attributes.

**10.14 — School page high contrast** — Respect prefers-contrast: high. Chart colors switch to high-contrast palette. Toggle in accessibility menu.

**10.15 — School page print styles** — Print hides nav, footer, sidebar. Shows header, key stats, restraint chart, data table, citation. Page breaks before sections.

#### School Production Hardening (5)

**10.16 — School route URL structure** — `/schools/{org_code}`. Also accept slug: `/schools/00160005/attleboro-high-school` redirects to canonical code URL. New column organizations.slug auto-generated.

**10.17 — School page meta tags** — Title: "[School] — [District] | Parent Data Force". Meta description auto-generated from data.

**10.18 — School sitemap inclusion** — All 1,813 school pages in sitemap.xml. Changefreq: monthly. Priority: 0.5. Active schools only.

**10.19 — School broken link detection** — Admin dashboard alert: count of school pages returning 404. Weekly script queries organizations and tests URLs.

**10.20 — School page analytics events** — Google Analytics events: school page views, chart interactions, comparison adds, report downloads. Category: school. Configurable on/off.
### Phase 11: SVG Map Foundation (10 subphases)

**11.1 — MA district boundary SVG source** — Obtain or create an SVG map of Massachusetts with one `<path>` per school district. Each path carries `data-code="00160000"` matching organizations.org_code. Source: U.S. Census Bureau TIGER/Line shapefiles → convert via mapshaper.org to simplified GeoJSON → convert to SVG paths with `data-code` attribute. Commit as `dev/public/assets/images/ma-districts.svg`. Fallback if unavailable: generate a grid-based abstract map (districts as rectangles positioned by lat/lon centroid).

**11.2 — SVG embedding strategy** — The SVG is embedded inline in the page (not `<img>`) to enable CSS styling and JS event binding. PHP helper `inline_svg(string $path): string` reads the SVG file and injects it into the page. Cached in memory for the request. SVG viewBox scaled to fit container via `width="100%" height="auto"`.

**11.3 — District path registry** — On page load, JS builds a `Map<string, SVGPathElement>` from all paths with `data-code`. Used for O(1) lookup when binding data. Fallback: paths without `data-code` are ignored (water bodies, state outline).

**11.4 — Map container and controls** — Map wrapped in `<div id="ma-heatmap">` with CSS: `max-width: 800px; margin: 0 auto; background: var(--bg-elevated); border-radius: var(--radius-md);`. Controls bar above map: metric selector dropdown, year selector, play/pause animation button, reset zoom button. Controls styled with existing `.form-select` and `.btn` classes.

**11.5 — Responsive map scaling** — SVG viewBox handles scaling natively. At < 768px: map fills container width, controls stack vertically. At < 480px: legend moves below map. Touch targets on mobile: minimum path hit area 20×20px (add transparent larger path overlay for small districts).

**11.6 — Map loading state** — While SVG loads and data binds: show skeleton placeholder (grey rectangle with pulse animation). Data fetched async from API. On error: "Map data unavailable" message with retry button.

**11.7 — District label system** — District abbreviations (first 4 letters of org_name) displayed on map for districts larger than a minimum pixel area. Labels styled: font-size 8px, fill `var(--text-muted)`, text-anchor middle. Smaller districts show label on hover only. Label placement via SVG `<text>` elements positioned at each path's centroid (pre-computed in the SVG source).

**11.8 — County overlay layer** — Second SVG layer: county boundaries as thicker semi-transparent strokes over the district map. Toggle in controls: "Show Counties". County labels in larger font, positioned at county centroid. Data from counties table (4.21).

**11.9 — Map projection validation** — Verify all 427 districts are represented in the SVG. Script queries `organizations.org_code` and cross-references against SVG `data-code` attributes. Missing districts listed in admin panel. Mismatches (districts in SVG but not in DB) logged. Run as part of data quality report (8.20).

**11.10 — Map dark theme styling** — SVG paths styled with CSS custom properties: fill (overridden by data binding), stroke `var(--border)`, stroke-width 0.5. Hover: stroke `var(--accent-glow)`, stroke-width 2. Water bodies: fill `var(--bg-secondary)`. State outline: stroke `var(--text-muted)`, stroke-width 1.5. All colors respect dark theme.

---

### Phase 12: Data-to-Map Binding (10 subphases)

**12.1 — Map data API endpoint** — New API type `map-data`. Accepts `&metric=restraint_rate|discipline_rate|attendance_rate|sped_grad|chronic_absent|enrollment` and `&school_year=`. Returns `[{org_code, org_name, value, percentile, rank}]`. One row per district. Value is the metric for the selected year. Percentile and rank pre-computed server-side. Cached with 300s TTL.

**12.2 — Data binding engine** — JS function `bindMapData(metric, year)`: fetches `/api/data?type=map-data&metric=X&school_year=Y`, iterates returned rows, for each `org_code` finds the SVG path via registry (11.3), sets `path.style.fill` based on color scale (12.3). Paths without data: fill `var(--bg-elevated)` (no data). Runs on page load and on control change.

**12.3 — Color scale function** — JS function `getColor(value, min, max, scale)`: returns CSS color string. Scale types: `diverging` (red-white-green for good/bad metrics), `sequential` (light-to-dark for neutral metrics), `categorical` (for org_type display). Color interpolation via d3-scale-chromatic or a hand-rolled linear interpolation between stops. Stops defined in a config object: `{diverging: ['#ef4444','#f59e0b','#e5e5e5','#22c55e','#16a34a'], sequential: ['#fef2f2','#fee2e2','#fca5a5','#ef4444','#b91c1c']}`.

**12.4 — Dynamic color scale range** — Min/max for color scale computed from the actual data range for the selected metric/year. Option to use fixed scale (0-100% for rates) or data-driven scale (highlights variation). Toggle in controls: "Scale: Auto | Fixed." Fixed scale enables fair comparison across years.

**12.5 — No-data handling** — Districts without data for the selected metric/year: fill with a distinct pattern (diagonal lines SVG `<pattern>`) and tooltip "No data available for [year]." Districts not in the SVG: shown in a "Not on map" list below the map (e.g., charter schools without geographic boundaries).

**12.6 — Data freshness indicator** — Below map: "Data: [school year]. Last updated: [date]." Timestamp from sync_log table. If data is older than 1 year, show amber warning "Data may be outdated — last refresh: [date]."

**12.7 — Pre-computed percentiles** — Map data API pre-computes percentiles for each district (rank / total districts × 100). Enables tooltip text: "[District] restraint rate: 12.7 per 100 (78th percentile — above average)." No client-side computation needed.

**12.8 — Multi-year data preload** — When the map page loads, fetch all available years' data in one API call: `&school_year=all`. Returns nested object `{2019: [...], 2020: [...], ...}`. Enables instant year switching without re-fetching. Cached aggressively (600s TTL). Large response (~400KB) but compressed via gzip in .htaccess.

**12.9 — Data binding debounce** — Rapid metric/year changes (e.g., scrubbing year slider) debounced at 150ms. Only the final value triggers re-bind. Prevents visual flicker and excessive DOM updates during scrubbing.

**12.10 — Binding error recovery** — If API request fails: show error banner "Unable to load map data. Retrying..." with auto-retry (3 attempts, exponential backoff: 1s, 3s, 9s). On final failure: show last cached data if available (from sessionStorage), else show "Data unavailable" with manual retry button.

---

### Phase 13: Color Scales and Legends (10 subphases)

**13.1 — Sequential color scale** — 7-stop gradient from light to dark orange: `#fff7ed` → `#fed7aa` → `#fdba74` → `#fb923c` → `#f97316` → `#ea580c` → `#9a3412`. Used for: enrollment (more = darker), restraint rate (higher = darker). Accessible: all stops have ≥3:1 contrast ratio against dark background.

**13.2 — Diverging color scale** — 7-stop gradient: `#16a34a` (good) → `#86efac` → `#dcfce7` → `#e5e5e5` (neutral/midpoint) → `#fecaca` → `#f87171` → `#dc2626` (bad). Used for: attendance rate (high=green), chronic absenteeism (low=green), SPED grad rate (high=green). Midpoint = state average.

**13.3 — Color-blind safe palette** — Alternative palette toggle: "Color-blind friendly". Uses blue-orange diverging scale (instead of red-green) and blue sequential scale (instead of orange). Toggle persisted in localStorage. Palette from ColorBrewer 2.0.

**13.4 — Legend component** — Custom SVG legend rendered below the map. Horizontal gradient bar with tick marks at each color stop. Labels: min value, 25th percentile, median, 75th percentile, max value. "State Avg" marker as a vertical line at the state average position. Legend width matches map width. Style: `font-family: Inter, font-size: 10px, fill: var(--text-secondary)`.

**13.5 — Categorical color scale** — For non-numeric displays (e.g., "Show by Org Type"): each org_type gets a distinct color from the 17-color palette. Legend shows org_type name + color swatch. Used for overview/exploration mode.

**13.6 — Bivariate color scale** — Advanced mode: two metrics encoded in one map. X-axis color (left-to-right) = metric 1, Y-axis color (top-to-bottom) = metric 2. Each district colored by a 3×3 or 4×4 grid of blended colors. Legend is a grid showing what each color means. Example: red+poor = high restraint + low attendance. Opt-in via "Advanced: Bivariate" toggle. Computed by mapping each metric to 3-4 quantile buckets, then looking up the 2D color.

**13.7 — Legend interactivity** — Hovering a legend color stop highlights all districts in that value range (others dim to 30% opacity). Clicking a legend stop filters the data table below (5.2 pattern) to show only those districts. "Clear filter" button resets.

**13.8 — Dynamic legend per metric** — Each metric defines its own legend configuration in a JS config object: `{metric: 'restraint_rate', label: 'Restraint Rate (per 100)', unit: '', scale: 'sequential', invert: false, midpoint: null}`. `invert` flips the color scale (e.g., higher restraint = worse = red in diverging). Legend renders from this config — no hardcoded per-metric logic.

**13.9 — Legend print compatibility** — Print stylesheet (5.6) ensures legend renders in greyscale-compatible patterns (hash patterns as SVG `<pattern>` fills) in addition to colors. Print legend shows both color and pattern. Ensures maps are interpretable when printed in B&W.

**13.10 — Legend accessibility** — Legend includes a hidden text description: "Map showing [metric name] by district for [school year]. Color ranges from [light color] ([min value]) to [dark color] ([max value]). State average: [value]." Screen reader reads this when focusing the map.

---

### Phase 14: Interactive Features (10 subphases)

**14.1 — District hover tooltip** — Hovering a district path: path stroke highlights to `var(--accent-glow)` (2px). Tooltip appears near cursor: district name, metric value, rank, percentile. Tooltip is a positioned `<div>` (not SVG `<title>` — too slow). Debounced at 50ms to prevent flicker. On touch: tap to show tooltip, tap elsewhere to dismiss.

**14.2 — District click navigation** — Clicking a district navigates to its detail page: `window.location = '/districts/' + org_code`. Ctrl+click opens in new tab. Middle-click opens in new tab (default browser behavior preserved). Visual feedback: brief flash animation on click (path fill pulses brighter for 300ms).

**14.3 — Map zoom and pan** — Mouse wheel zooms in/out centered on cursor. Click+drag pans. Zoom range: 1× (full state) to 8× (single district). Reset zoom button (14.1) returns to full state. On zoom > 4×: district labels appear for all visible districts. Smooth transitions via CSS `transform` on an SVG `<g>` wrapper element. Touch: pinch-to-zoom, two-finger pan.

**14.4 — Zoom-to-district** — On district detail page: "Show on Map" link opens map page with that district pre-zoomed and highlighted. URL param: `/data/map?district=00160000&zoom=4`. JS reads params on load and animates zoom to the district.

**14.5 — District search on map** — Search input above map: type district name → dropdown of matches (from existing search API). Selecting a district: map zooms to it, highlights it with pulsing border, shows tooltip. "Clear" button resets view. Same search component as district list (4.5).

**14.6 — Multi-select highlight** — Ctrl+click or Shift+click to select multiple districts. Selected districts highlighted with bold stroke. Comparison panel appears on the side: mini bar chart comparing selected districts on current metric. "Compare Selected (N)" button → `/compare` with pre-filled codes. Max 5 selections.

**14.7 — Map crosshair coordinate display** — Hovering the map shows crosshair lines (vertical + horizontal through cursor) and a coordinate display in the corner: "Cursor: [town name approximation]". Town names estimated from district centroid proximity. Purely cosmetic — helps orient the user.

**14.8 — Map keyboard navigation** — Tab into map. Arrow keys move focus between adjacent districts (spatial adjacency pre-computed from SVG path neighbors). Enter: zoom to focused district. Escape: reset zoom. Plus/Minus: zoom in/out. 0: reset. Keyboard shortcuts displayed in a "?" help overlay.

**14.9 — Map bookmarks** — "Bookmark this view" button: saves current metric, year, zoom level, and visible area to a URL. Copy-to-clipboard button. Shareable link: `/data/map?metric=restraint_rate&year=2023-24&zoom=3&x=0.4&y=0.3`. Restoring the link returns to the exact view.

**14.10 — Map loading progress** — Large SVG may take time to parse. Show a progress indicator: "Loading map..." → "Loading data..." → "Rendering...". Each stage with a checkmark when complete. Total load time displayed in corner (dev mode): "Map ready in 1.2s."

---

### Phase 15: Multi-Metric Views (10 subphases)

**15.1 — Metric selector dropdown** — Dropdown above map: "Select Metric". Options grouped: "Restraint" (rate, total, injuries), "Discipline" (suspension rate, expulsion rate), "Demographics" (SPED %, low-income %), "Outcomes" (attendance, SPED grad rate). Each option shows current year's state average in grey. Selecting changes map colors and legend.

**15.2 — Side-by-side dual map** — Toggle: "Compare Two Metrics". Splits map area into two maps side by side (each 50% width). Each map has its own metric selector and legend. Hovering a district on either map highlights it on both. Enables visual correlation: "Are high-restraint districts also low-attendance?" At < 768px: maps stack vertically.

**15.3 — Difference map** — "Show Change" mode: compares two years (year A vs year B dropdowns). Map shows the difference (year B − year A). Diverging color scale: blue = decreased (improved), white = no change, red = increased (worsened). Legend shows range of change values. Reveals which districts are improving or declining.

**15.4 — Small multiples grid** — "Show All Years" mode: 2×4 or 3×3 grid of small maps, each showing one school year. All maps share the same color scale. Arranged chronologically left-to-right, top-to-bottom. Clicking any small map enlarges it to full size. Powerful for spotting temporal patterns.

**15.5 — Metric correlation view** — Below the dual map (15.2): scatter plot of the two selected metrics. X = metric 1 value, Y = metric 2 value. Each point = one district. Color = district's color on the map. Points and map linked: hovering a point highlights the district on BOTH maps. Correlation coefficient displayed.

**15.6 — Top/bottom N highlight** — Toggle: "Highlight Top 10 | Bottom 10 | None". Top 10 districts (highest values) highlighted with gold stroke on map; bottom 10 with silver stroke. Others dimmed to 30% opacity. Quick way to identify extremes without scanning the full map.

**15.7 — Metric info panel** — Sidebar panel: explains the selected metric (definition, data source, calculation method). "How is this calculated?" expandable section. Links to DESE documentation. "Metric last updated: [date]." Helps users understand what they're looking at.

**15.8 — Custom metric builder** — Advanced feature: create a derived metric from existing ones. Formula input: `(restraint_rate * 0.4) + (discipline_rate * 0.3) + ((100 - attendance_rate) * 0.3)`. Parsed via a simple expression evaluator in PHP. Result computed per district and displayed on map. "Save Custom Metric" stores in localStorage. "Share" generates a URL with the formula encoded.

**15.9 — Metric presets** — Curated preset buttons above the map: "Restraint Overview", "Equity Lens", "Academic Outcomes", "District Size". Each preset sets the metric, year, and color scale to a recommended configuration. "Save Current as Preset" for power users. Presets stored in localStorage.

**15.10 — Embeddable map widget** — "Embed" button generates an `<iframe>` code snippet: `<iframe src="/embed/map?metric=restraint_rate&year=2023-24" width="600" height="400"></iframe>`. Embed page is a minimal version of the map (no nav, no footer, just map + legend). CSP allows embedding on any domain. Used for sharing on other websites.

---

### Phase 16: Temporal Animation (10 subphases)

**16.1 — Year slider control** — Range slider below map: min=earliest year, max=latest year, step=1. Dragging the thumb updates the map in real-time (debounced 100ms). Current year displayed above the slider. Play/pause button to the left of the slider.

**16.2 — Auto-play animation** — "Play" button starts animation: cycles through all years at 1.5s per year. Loops back to start on completion. Color scale fixed across all years (not re-normalizing per year) to show absolute changes. Pause button stops; slider scrub resumes manual control. Speed control: 0.5×, 1×, 2×.

**16.3 — Year transition smoothing** — When switching years (via slider or animation), district colors transition smoothly over 300ms using CSS `transition: fill 300ms ease`. No abrupt color jumps. Paths store their current fill and target fill; CSS handles the interpolation.

**16.4 — Year-over-year delta overlay** — Toggle: "Show Year-over-Year Change". Instead of absolute values, map shows the change from previous year. Blue = improved, red = worsened. Default metric is restraint rate. Makes it easy to spot which districts had the biggest single-year shifts.

**16.5 — Cumulative change view** — "Show Cumulative Change": map shows change from a baseline year (selectable, default: earliest year) to the currently displayed year. Reveals long-term trajectories. Diverging scale. "Reset baseline" button.

**16.6 — Animated district trails** — On a line chart below the map: select up to 5 districts. Each district's metric is plotted as a line over all years. As the map animation plays, a marker moves along each line showing the current year. Map colors correspond to line colors. Connects the spatial and temporal views.

**16.7 — Significant change detection** — During animation, districts with large year-over-year changes (≥2σ) pulse briefly (scale transform 1.0→1.05→1.0 over 500ms). Draws attention to notable shifts. "Show only significant changes" toggle dims everything else to 10% opacity.

**16.8 — Animation narration** — Optional auto-generated text panel that updates with each animation frame: "In 2020-21, statewide restraint rates dropped 40% due to COVID-19 school closures. By 2023-24, rates had rebounded to pre-pandemic levels." Narration text is pre-written for known events (COVID) and auto-generated for others. Toggle on/off.

**16.9 — Animation export as GIF/MP4** — "Export Animation" button: captures the animated map as an animated GIF (client-side via gif.js library from CDN) or as a series of PNG frames (one per year). Downloads a zip of frames. "Export as video" placeholder for server-side ffmpeg rendering (future enhancement).

**16.10 — Animation bookmarks** — During animation, "Bookmark this year" button saves the current year as a named bookmark in a list below the map. "Jump to bookmark" clicks animate the map to that year. Bookmarks stored in localStorage. Export bookmarks as a list of URLs.

---

### Phase 17: Drill-Down and Detail (10 subphases)

**17.1 — District detail slide-out panel** — Clicking a district (14.2 behavior modified): instead of navigating away, a slide-out panel opens from the right side of the map. Panel shows: district name, key stats, mini sparkline chart of current metric over all years. "View Full Profile" button navigates to district detail. "Close" or click outside dismisses. Keeps user in the map context.

**17.2 — School overlay toggle** — "Show Schools" checkbox overlays small circles on the map at each school's approximate location (pre-computed lat/lon centroids stored in organizations table, or estimated from town center + jitter). Circle radius proportional to enrollment. Circle color matches district color. Hover: school name + restraint rate. Click: opens school detail page. Performance: schools rendered as a single SVG `<g>` layer, toggled via CSS `display`.

**17.3 — County-level aggregation toggle** — "Aggregate by County" switch: instead of coloring individual districts, the map colors counties. District boundaries fade to 20% opacity; county boundaries (11.8) become primary. Data aggregated server-side via API (4.22). Toggle animates transition between district and county views.

**17.4 — District detail in map context** — When a district is selected (slide-out open): the map auto-zooms to that district (if zoom < 4×). Adjacent districts are labeled. "Zoom to district" and "Zoom to state" buttons in the slide-out. "Show schools in this district" checkbox (17.2 scoped to selected district only).

**17.5 — Compare mode on map** — "Compare Mode" toggle: split the map vertically with a draggable divider. Left side = metric A / year A. Right side = metric B / year B. Both sides share zoom/pan. Hover syncs across both sides. Ideal for before/after comparisons. At < 768px: stacked vertically.

**17.6 — Lasso select** — Draw a freeform lasso on the map (click to start polygon, click to add points, double-click to close). All districts whose centroid falls within the lasso are selected. Shows summary stats for the selected area: "X districts selected. Avg restraint rate: Y.Y. Total enrollment: Z,ZZZ." "Compare Selected" and "Export Selected" buttons.

**17.7 — Radius select** — Click a district, then "Select within X miles." Draws a circle overlay centered on the district at the specified radius. All districts whose centroids fall within the circle are selected. Useful for regional analysis. Distance computed from centroid lat/lon.

**17.8 — Drill-down breadcrumb** — As the user zooms and selects, a breadcrumb trail appears: "Massachusetts → [County] → [District] → [School]." Clicking any breadcrumb zooms/selects that level. Provides spatial orientation and easy navigation back up the hierarchy.

**17.9 — Related content sidebar** — When a district is selected (slide-out open): "Related" section shows articles and cases linked to this district (from article_org_links and cases.org_id). "Related Districts" shows similar districts (from 4.16). "Nearby Districts" shows geographically adjacent districts. All with thumbnail charts and links.

**17.10 — Map-to-table sync** — Below the map: a data table showing all districts, sorted by the current metric value. Table rows and map paths are linked: hovering a table row highlights the map path; clicking a map path scrolls the table to that row. "Sort by: Name | Value | Rank." Table respects map zoom level: when zoomed in, table filters to only visible districts.

---

### Phase 18: Comparison and Export (10 subphases)

**18.1 — Side-by-side map comparison** — Independent of compare mode (17.5): "Compare [Year A] vs [Year B]" generates two full-size maps stacked or side-by-side, both with the same color scale. Below: difference map (15.3) and a table of districts with the largest changes. Export as a single composite image or PDF.

**18.2 — Map snapshot export as PNG** — "Download Map as PNG" button. Uses the Canvas API: serializes the SVG to a data URL, draws it on a hidden `<canvas>` at 2× resolution (for Retina), includes legend and title. Downloads as `ma-[metric]-[year].png`. Client-side only. Max resolution: 2400×1800px.

**18.3 — Map export as SVG** — "Download Map as SVG" button. Downloads the current map state as a standalone `.svg` file including: embedded CSS, current data colors, legend, title, data source citation. Openable in Illustrator/Inkscape for further editing. Generated by cloning the map DOM, inlining computed styles, and triggering download.

**18.4 — Map export as PDF report** — "Download Map Report" button. Generates a printable HTML page with: full-page map, legend, data table of visible districts, methodology notes, data source. Uses `window.print()` with print stylesheet (5.6). Designed for letter-size paper.

**18.5 — Map animation export as GIF** — Extends 16.9. Client-side GIF generation using gif.js library (loaded from CDN). Captures each animation frame at 1× resolution. Max 30 frames (years). Progress bar during generation. Downloads as `.gif`. Server-side alternative: PHP script that uses ImageMagick to composite frames (if available).

**18.6 — Map comparison export** — When in compare mode (17.5): export generates a composite image showing both maps side-by-side with a shared legend and a "Difference" inset. Single download. Also available as PDF report.

**18.7 — Map data table export** — "Download Map Data as CSV" exports the data behind the current map view (all visible districts with org_code, org_name, metric value, rank, percentile). Uses existing CSV infrastructure (5.2). Respects current map filters (zoom level, top/bottom N, lasso selection).

**18.8 — Map embed code export** — "Get Embed Code" generates an `<iframe>` snippet (15.10) pre-configured with the current metric, year, and view. Also generates a static thumbnail image link for social sharing: `<meta og:image>` URL pointing to a server-rendered map PNG.

**18.9 — Batch map export** — "Export All Years" generates a zip file containing one PNG per school year for the current metric. All maps share the same color scale. "Export All Metrics" generates one PNG per metric for the current year. Server-side generation via a PHP script that uses the same data + a headless SVG renderer (or falls back to client-side with a progress UI).

**18.10 — Print-optimized map page** — New route `/data/map/print?metric=X&year=Y`. Full-page map designed for printing: white background, black district borders, color fill with crosshatch pattern overlay (for B&W printers), large legend, data source, URL. No interactive controls. `@media print` in print.css styles this page.

---

### Phase 19: Performance and Polish (10 subphases)

**19.1 — SVG path simplification** — The source SVG should use simplified district boundaries (tolerance ~500m) to keep file size under 500KB. Original detailed boundaries are ~5MB. Simplified boundaries still recognizable. If detailed boundaries are needed for a specific zoom level, load them on demand.

**19.2 — Virtualized district rendering** — At zoom levels where only a subset of districts is visible: only render paths that intersect the viewport. Reduces DOM size and improves pan/zoom performance. Paths outside viewport get `display: none`. Re-computed on each zoom/pan event (debounced 100ms).

**19.3 — Map data caching** — Map data API responses cached via `Database::fetchAllCached` with 600s TTL. Client-side: fetched data stored in sessionStorage keyed by `map-data-{metric}-{year}`. On page load, check sessionStorage first; if fresh (< 10 min), use cached; else fetch. "Refresh Data" button forces re-fetch.

**19.4 — SVG rendering performance** — SVG `<path>` elements use `will-change: fill` CSS hint for GPU-accelerated color transitions. Color binding updates paths via direct `style.fill = ...` rather than class toggling (avoids style recalculation). Batch DOM updates in a single `requestAnimationFrame` callback.

**19.5 — Map page load optimization** — Map page loads in stages: (1) HTML shell + CSS renders immediately (< 200ms), (2) SVG skeleton (state outline only) renders (< 400ms), (3) full SVG with all districts renders (< 800ms), (4) data binds and colors appear (< 1.2s). Each stage visible to the user — no blank white screen. Lazy load: map JS and SVG loaded via dynamic `import()` or script injection.

**19.6 — Mobile touch optimization** — On mobile: pinch-to-zoom uses the browser's native viewport zoom (not custom JS zoom — smoother). Tooltip appears above the touch point (not under the finger). District tap targets enlarged to 44×44px minimum. "Reset View" floating button always visible. Controls collapse to a bottom sheet that slides up.

**19.7 — Offline map support** — Service Worker (if implemented in the future) caches the SVG and map JS. Map data API responses cached in IndexedDB. "You're offline — showing cached data from [date]." Banner when offline. Last successful data fetch always available.

**19.8 — Map analytics events** — Google Analytics events (if configured): metric changes, year changes, district clicks, zoom/pan, compare mode toggle, export downloads. Event category: 'map', action: 'metric_change|year_change|district_click|zoom|export', label: metric name or district code. Helps understand how users explore the data.

**19.9 — Map error telemetry** — Client-side error logging: SVG parse failures, data binding errors, API timeouts. Errors logged to `console.error` and optionally sent to a `/api/log` endpoint (if enabled in system_config). "Something went wrong" user-facing message with a "Send Error Report" button (opt-in).

**19.10 — Cross-browser testing checklist** — Verify map renders correctly on: Chrome 120+, Firefox 120+, Safari 17+, Edge 120+. Test matrix: SVG rendering, color interpolation, zoom/pan smoothness, tooltip positioning, animation frame rate (target: 30fps+ during year animation), export functionality. Document any browser-specific workarounds.

---

### Phase 20: Integration and Deployment (10 subphases)

**20.1 — Map page route** — New route `/data/map` → `DataPortalController::map()`. New view `dev/app/views/data-map.php`. Linked from: data portal hub card "Explore Map", nav dropdown (Data → Map), district detail page ("View on Map" button), homepage data section.

**20.2 — Map embed route** — New route `/embed/map` → `EmbedController::map()`. Minimal layout: no nav, no footer, just map + legend + controls. Used for iframe embeds (15.10). CSP header allows embedding on any origin. Separate view template: `dev/app/views/embed/map.php`. CSS: `body { margin: 0; background: var(--bg-primary); }`.

**20.3 — Admin map configuration** — Admin page `/admin/map`: configure default metric, default year, color scale type, whether to show county overlay by default. Stored in system_config. "Preview" button shows the map with current settings. "Reset to defaults" button.

**20.4 — CSP update for map resources** — Update `.htaccess` Content-Security-Policy to allow: inline SVG (`style-src 'unsafe-inline'` already set), GIF export library CDN (`script-src https://cdn.jsdelivr.net` already set), map data API (`connect-src 'self'` already set). No new CSP entries needed — verify existing policy covers all map dependencies.

**20.5 — Map SVG in source control** — Commit `ma-districts.svg` to `dev/public/assets/images/`. Add `.gitattributes` entry for binary SVG. If SVG is generated from shapefiles, also commit the generation script to `dev/scripts/generate_map_svg.py` and the simplified GeoJSON source to `dev/scripts/map-data/`. Document the update process in a README.

**20.6 — Map data freshness automation** — The map data API pulls from the same DESE tables as the rest of the platform. When DESE data is refreshed (via import scripts), the map automatically shows new data on next cache expiry (600s). "Refresh Map Data" button in admin clears the map cache. No separate map data pipeline needed.

**20.7 — Map integration with compare tool** — On `/compare`: "View on Map" button next to each selected district. Opens the map page with those districts highlighted (14.6). On map page: "Compare Selected" button navigates to `/compare` with pre-filled district codes. Bidirectional linking between map and comparison tools.

**20.8 — Map integration with district detail** — Every district detail page has a small inline map in the sidebar or header: 200×150px miniature district map showing the district's position within the state. The district is highlighted in orange; the rest of the state is grey. "View Full Map" link. Generated server-side: PHP renders a simplified SVG snippet with just the state outline + the district path filled.

**20.9 — Map on homepage** — Homepage data glance section (below the restraint chart): a 400×300px miniature heatmap of Massachusetts showing the currently selected metric. Dropdown switches metric. "Explore Full Map" link. Serves as a teaser — invites exploration. Rendered via the same JS as the full map but at reduced size.

**20.10 — Map documentation page** — New section in `/data/help` (5.20): "How to Use the Map." Explains: metric selection, year slider, zoom/pan, district click, compare mode, export options. Includes annotated screenshots. Links to DESE data definitions for each metric. "Video Walkthrough" placeholder for future screen recording.
## Critical files & anchors

| File | Symbol/Region | Why |
|------|--------------|-----|
| `dev/app/Components/Chart.php` | New — entire class | Hub component. Every chart in the app renders through this. |
| `dev/app/Controllers/ApiController.php` | `data()` method lines 10-80 | All chart data endpoints route through here. Add 8 new `case` branches. |
| `dev/app/views/data/browser.php` | Entire file | Shared template for all 6 data browsers. Add conditional chart blocks before the table. |
| `dev/app/views/district.php` | Dashboard grid lines 30-60 | Replace text stat panels with chart canvases. Add hero bar, timeline, similar districts. |
| `dev/app/Controllers/DataPortalController.php` | `dashboard()` (new), `compare()` lines 20-60 | Dashboard controller; fix compare aggregation query. |

## Verification

**Phase 1**: Visit `/api/data?type=discipline-breakdown` → JSON array. Create test PHP file that instantiates `Chart('test','bar')`, adds 2 datasets, calls `render()` → valid `<canvas>` + `<script>` with no JS errors.

**Phase 2**: Visit `/data/discipline` → stacked bar chart above table. Click bar → table filters. All 6 browsers have charts. Restraint heat-map toggle works.

**Phase 3**: Visit `/data/dashboard` → 6 stat cards show real numbers with YoY indicators. 2×2 grid of 4 charts renders. Correlation scatter plots show r values. Rankings table sortable. "Biggest movers" section populated.

**Phase 4**: Visit `/districts` → sparklines and badges on cards. Search-as-you-type filters. Visit `/districts/00160000` → hero bar, 3 mini-charts, similar districts sidebar, peer percentiles, timeline. Visit `/compare?districts[]=00160000&districts[]=00350000` → radar chart + grouped bar + summary card + export button.

**Phase 5**: Click "Download CSV" on any ranking → file downloads. Print preview → clean layout. Screen reader announces chart descriptions. `/data/dashboard` loads in < 2s with cache warm. All 30+ chart types render on smoke test page in < 3s.

## Assumptions & contingencies

- Chart.js 4.4.0 CDN remains available. If CDN blocked, fallback to local copy at `dev/public/assets/js/chart.min.js` (commit the file).
- All 6 DESE datasets loaded (verified). If a dataset is empty, chart renders "No data" message, API returns `[]`.
- Massachusetts district boundary GeoJSON for Phase 4.17 is an external dependency. If unavailable, skip map and replace with a horizontal bar chart of top districts.
- Browser support: modern Chrome/Firefox/Safari/Edge. IE11 not supported. Chart.js canvas fallback: `<noscript>` message for JS-disabled users.
- `Chart.php` uses PHP 8.x constructor promotion and named arguments. If PHP < 8.0 on production, refactor to traditional constructor — check production PHP version first.
