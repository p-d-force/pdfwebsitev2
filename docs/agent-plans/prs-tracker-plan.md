# PRS Case Tracker & Analytics — 10 Phases × 300 Subphases

## Context

The platform holds 8,550 PRS (Problem Resolution System) complaint records in `prs_intakes` but has no dedicated tracker, timeline, analytics, or cross-reference views. This plan delivers a complete PRS case management and analytics system: individual case timelines with document linking, geographic heatmaps, year-over-year trends, cross-correlation with DESE district data (restraint, discipline, demographics), deadline tracking, finding analysis, and full export/reporting. Every visualization reuses the existing `Chart` component (`dev/app/Components/Chart.php`), SVG map infrastructure, and color scale system from the main visualization plan.

## Approach

### Phase 1: PRS Data Model & Infrastructure (30 subphases)

#### Core Data Model (6)

**1.1 — PRS cases table** — Create `prs_cases` table: id, prs_number (UNIQUE), org_id FK, case_title, case_description, filing_date, acceptance_date, investigation_start, findings_issued_date, closure_date, current_status ENUM(filed,accepted,investigating,findings,closed,appealed), resolution_type ENUM(substantiated,unsubstantiated,partially_substantiated,resolved,withdrawn,dismissed), complainant_type, allegations JSON, findings_summary TEXT, corrective_actions TEXT, deso_reference_url, created_at, updated_at. Migrate existing `prs_intakes` data into this table, mapping status to current_status and closure_code to resolution_type.

**1.2 — PRS events timeline table** — Create `prs_events` table: id, prs_case_id FK, event_date, event_type ENUM(filed,acknowledged,assigned,extension_requested,extension_granted,investigation_opened,interview_conducted,site_visit,preliminary_findings,district_response,findings_issued,corrective_action_ordered,compliance_verified,closed,appealed,reopened), event_description TEXT, actor VARCHAR(255), created_at. Each PRS case has multiple events forming a chronological timeline. Data seeded from existing `prs_intakes` (intake_date → filed event, findings_date → findings_issued event) and from document dates in `documents` table linked via document_links.

**1.3 — PRS deadline tracking fields** — Add columns to `prs_cases`: statutory_deadline DATE (60 calendar days from acceptance per DESE regulations), extended_deadline DATE, actual_resolution_date DATE, overdue_at_filing BOOLEAN (whether DESE missed the 60-day deadline), days_to_acceptance INT, days_to_findings INT, total_days_open INT. All computed via SQL triggers or PHP on insert/update of events. Pre-computed for existing records via migration script.

**1.4 — PRS finding details table** — Create `prs_findings` table: id, prs_case_id FK, finding_number INT, allegation_category VARCHAR(100), allegation_subcategory VARCHAR(100), finding ENUM(substantiated,unsubstantiated,partially_substantiated), finding_detail TEXT, cited_regulation VARCHAR(255), corrective_action_ordered TEXT, corrective_action_status ENUM(pending,in_progress,completed,overdue), corrective_action_deadline DATE, compliance_verified_date DATE. One PRS case can have multiple findings. Data seeded from existing `prs_intakes` category/subcategory.

**1.5 — PRS-to-document linking** — Use existing `document_links` table: insert rows with target_type='prs_case', target_id=prs_cases.id, link_type='evidence|finding|correspondence|determination'. Migration: scan `documents` table doc_family='prs' and match to prs_cases by org_id + date proximity. Admin can manually link/unlink documents from PRS case detail page.

**1.6 — PRS category taxonomy table** — Create `prs_categories` table: id, category_name, category_slug, parent_id FK self, description. Seed with known DESE PRS categories: Special Education (IEP implementation, evaluation, placement, discipline, restraint, transportation, related services), Civil Rights (discrimination, harassment, access), English Learners, Title I, Career/Voc Tech, Other. `prs_cases.allegations` JSON references category IDs. Enables hierarchical drill-down in analytics.

#### PRS API Endpoints (6)

**1.7 — PRS case list API** — `/api/prs/cases`. Returns paginated list of PRS cases. Params: `?status=&category=&district=&year=&page=&per_page=`. Response: `{data: [{id, prs_number, title, org_name, status, filing_date, days_open, finding_count}], pagination: {...}}`. Uses optimized query with JOIN to organizations.

**1.8 — PRS case detail API** — `/api/prs/cases/{id}`. Returns full case: case details, all events (chronological), all findings, linked documents (via document_links JOIN), deadline status. Single endpoint serves the entire detail page.

**1.9 — PRS analytics API** — `/api/prs/analytics`. Params: `?type=category_breakdown|status_distribution|timeline_trends|resolution_rates|deadline_compliance|district_volume|year_over_year`. Each type returns pre-aggregated data. Cached 300s. Used by all PRS charts.

**1.10 — PRS timeline API** — `/api/prs/timeline`. Params: `?case_id=` or `?district=` or `?year=`. Returns chronological event list or aggregated timeline data for visualization. If district, returns all events for all PRS cases in that district, grouped by case.

**1.11 — PRS cross-reference API** — `/api/prs/cross-ref`. Params: `?district=` and `?metrics=restraint_rate,discipline_rate,attendance_rate`. Returns PRS metrics alongside DESE metrics for the district across all years. Enables correlation charts.

**1.12 — PRS search API** — Extend existing `/api/search` with `&type=prs`. Full-text search across prs_cases.title, prs_cases.case_description, prs_findings.finding_detail. Returns matching cases with relevance snippets.

#### PRS Page Routes (6)

**1.13 — PRS case list page** — New route `/prs` → `PrsController::list()`. Paginated list of all PRS cases. Filter bar: status dropdown, category dropdown, district search, year range, keyword search. Each row: PRS number, title (truncated), district, status badge, filing date, days open, finding count. Sortable by any column. Click row → case detail.

**1.14 — PRS case detail page** — New route `/prs/{prs_number}` → `PrsController::show()`. Full case view: header (PRS number, title, status badge, district link), meta grid (filing date, acceptance date, findings date, closure date, days open, deadline status), allegation list, findings list with outcomes, event timeline, linked documents, corrective actions with status. "View District Profile" link. Admin: "Edit Case" link.

**1.15 — PRS analytics dashboard** — New route `/prs/analytics` → `PrsController::analytics()`. Full-page dashboard: stat cards row (total cases, open cases, avg resolution days, deadline compliance %), 2×3 chart grid (category breakdown pie, status distribution bar, filing trend line, resolution time histogram, district volume bar, YoY change bar). Filters: year range, district, category.

**1.16 — PRS category explorer** — New route `/prs/categories` → `PrsController::categories()`. Hierarchical view of PRS categories. Each category: description, case count, avg resolution days, substantiation rate, trend sparkline. Click category → filtered case list. Category tree navigable via sidebar.

**1.17 — PRS district view** — New route `/prs/district/{code}` → `PrsController::districtView()`. All PRS cases for a specific district. Summary stats: total cases, open cases, resolution rate, avg days. Charts: filing trend, category breakdown, status distribution. Case list below. Also embedded as a tab on the district detail page.

**1.18 — PRS map view** — New route `/prs/map` → `PrsController::map()`. Reuses SVG map infrastructure (Phase 11-20 of main viz plan). Map colored by PRS metric: case count per district, complaint rate per 1000 students, avg resolution days, substantiation rate. Year slider. Click district → district PRS view.

#### PRS Admin & Data Quality (6)

**1.19 — PRS case CRUD** — Admin controllers: `AdminPrsController` with list, create, edit, delete. Form fields: all prs_cases columns, dynamic finding sub-form (add/remove finding rows), event sub-form (add event with date/type/description), document linker (search and attach existing documents). CSRF on all POSTs. Audit logging on all changes.

**1.20 — PRS bulk import** — Admin page `/admin/prs/import`: upload CSV of PRS cases. Maps columns to prs_cases fields. Preview before import. Validates: prs_number uniqueness, org_code → org_id resolution, date formats. Error rows flagged. Successful rows inserted. Audit log records import with row count. Matches existing pattern from DESE import scripts.

**1.21 — PRS data quality checks** — SQL queries run weekly (or via admin button): orphan events (prs_events without valid prs_case_id), missing deadlines (cases with findings but no deadline set), date inconsistencies (findings_date before filing_date), duplicate PRS numbers, cases with no events, cases with org_id=NULL. Results displayed on admin data quality dashboard.

**1.22 — PRS deadline alerts** — Admin dashboard widget: "Approaching Deadlines" — cases where statutory_deadline is within 7 days and status != 'closed'. "Overdue Cases" — cases past deadline without resolution. Color-coded: amber (≤14 days), red (≤7 days), dark red (overdue). Click → case detail.

**1.23 — PRS status lifecycle rules** — PHP validation class `PrsStatusRules`: defines valid status transitions (filed→accepted→investigating→findings→closed, with appeals and reopening as alternate paths). Admin edit form enforces these. Invalid transitions rejected with message. "Force transition" superadmin override with audit log.

**1.24 — PRS duplicate detection** — Algorithm: cases with same district, same category, filing dates within 30 days flagged as potential duplicates. "Possible duplicate: PRS-2024-0123" shown on case detail. Admin can mark as "Not a duplicate" or merge cases (events, findings, documents consolidated into primary case, secondary case closed with note).

#### PRS Core Charts (6)

**1.25 — PRS category breakdown chart** — Doughnut or treemap chart on analytics dashboard. Segments = top 8 complaint categories + "Other". Uses Chart component with categorical color palette. Click segment → filtered case list. Tooltip: count, %, avg resolution days.

**1.26 — PRS filing trend chart** — Multi-line chart: X = school year (or calendar year), Y = number of PRS cases filed. Multiple lines: total, by top 5 categories, by top 5 districts. Toggle lines via legend click. Year-over-year % change annotations at each data point.

**1.27 — PRS status flow diagram** — Sankey diagram showing flow of cases through statuses: filed → accepted → investigating → findings → closed, with branch widths proportional to case counts. Alternative paths visible: filed→dismissed, closed→appealed. Uses Chart.js Sankey plugin or D3 sankey (loaded from CDN). Reveals where cases bottleneck.

**1.28 — PRS resolution time histogram** — Bar chart: X = days to resolution (buckets: 0-30, 31-60, 61-90, 91-120, 120+), Y = number of cases. Color: green (≤60 days), amber (61-90), red (90+). Vertical line at 60-day statutory deadline. Separate bars for substantiated vs unsubstantiated. Tooltip: count, %.

**1.29 — PRS complainant type breakdown** — Pie chart showing who files PRS complaints: parent/guardian, advocate, attorney, organization, anonymous, other. Data from prs_cases.complainant_type. Color-coded. Click segment to filter cases. "Unknown" segment highlighted differently (needs data cleanup).

**1.30 — PRS heatmap calendar** — Calendar heatmap (like GitHub contribution graph): one cell per day, colored by number of PRS filings on that day. Year selector. Reveals seasonal patterns (e.g., more filings in September after school year starts). Pure CSS grid with inline style backgrounds (no canvas needed). Hover: date + count + list of case numbers.

---

### Phase 2: PRS Case Detail & Timeline (30 subphases)

#### Case Detail Page (6)

**2.1 — PRS case header component** — Reusable PHP component `PrsCaseHeader`: renders PRS number, title, status badge (color-coded), district link (→ `/districts/{code}`), "Last updated: [date]" timestamp. Admin: "Edit", "Add Event", "Add Finding" buttons. Used on detail page and embedded views.

**2.2 — PRS case meta grid** — Grid of key-value pairs: Filing Date, Acceptance Date, Investigation Opened, Findings Issued, Closure Date, Statutory Deadline (with overdue indicator), Days Open, Days to Resolution, Complainant Type, Resolution Type. Each value computed from prs_cases fields. Missing values show "—" in muted color. Overdue deadlines show red text with "Overdue by X days."

**2.3 — PRS allegation list** — Expandable list of allegations from prs_cases.allegations JSON. Each allegation: category (linked to category page), subcategory, description. "Show All (N)" expand/collapse. If allegations reference specific regulations, show regulation citation as a link to DESE website.

**2.4 — PRS finding cards** — Each finding from prs_findings rendered as a card: finding number, category, finding result (color-coded: substantiated=red, unsubstantiated=grey, partially=amber), finding detail text, corrective action ordered (with status badge: pending/completed/overdue), cited regulation, compliance date. Cards sorted by finding_number.

**2.5 — PRS linked documents panel** — List of documents linked to this PRS case via document_links. Each row: document title, type, date, file size, "View" link (→ `/documents/{id}`). "Upload New Document" button (admin only): opens upload form, auto-links to this case. Documents grouped by link_type (evidence, finding, correspondence, determination).

**2.6 — PRS corrective action tracker** — Table of corrective actions across all findings: action description, responsible party, deadline, status (color-coded progress bar: not started=0%, in progress=50%, completed=100%, overdue=red 100%), compliance date. "Mark Complete" button (admin) with date picker. "Extend Deadline" with reason field.

#### Interactive Timeline (6)

**2.7 — Vertical timeline component** — New PHP component `Timeline`: accepts array of events with date, type, title, description. Renders as a vertical line with dots at each event date. Dot color by event_type (filed=blue, accepted=green, investigating=amber, findings=red, closed=grey, appealed=purple). Events ordered chronologically. Current status highlighted with larger dot + glow. CSS: left border line, absolute-positioned dots, flexbox rows for event cards. Mobile: dots on left, cards full-width.

**2.8 — Timeline event cards** — Each timeline event: date (left), event type badge (colored), title (bold), description (grey text), actor (small text: "by [name]"). Clicking a card expands to show full description. "Edit" and "Delete" buttons (admin only) with confirmation. "Add Event After This" button inserts in chronological order.

**2.9 — Timeline zoom levels** — Toggle: "Compact" (one-line per event, all visible), "Detailed" (full cards, scrollable), "Calendar" (events on a monthly calendar grid). Default: Detailed. Compact useful for cases with 50+ events. Calendar view shows event density.

**2.10 — Timeline date gap detection** — Auto-highlight gaps between events > 30 days with a grey "dormant period" bar on the timeline. Tooltip: "No activity for X days (from [date] to [date])." Helps identify where cases stalled. Admin can add a "Note" event explaining the gap.

**2.11 — Timeline document overlay** — Documents linked to the case appear as small document icons on the timeline at their document_date position. Hover shows document title + type. Click opens document viewer. Visual distinction: PDF icon, email icon, spreadsheet icon by file_mime.

**2.12 — Timeline deadline markers** — Vertical red dashed line at the statutory deadline date. Label: "60-Day Deadline — [date]." If case exceeded deadline: red shaded area from deadline to actual resolution. Tooltip: "Exceeded by X days." Amber line at 45 days: "45-Day Warning."

#### Deadline Tracking (6)

**2.13 — Deadline calculation engine** — PHP class `PrsDeadlineCalculator`: accepts filing_date, calculates statutory_deadline (filing_date + 60 calendar days per 603 CMR 28.09). Handles: weekends don't extend (calendar days, not business days), DESE extensions (+30 days per extension event), tolling periods (events where clock pauses, e.g., awaiting district response). Stored as prs_cases columns, recalculated on event changes.

**2.14 — Deadline countdown widget** — On case detail: large countdown showing days remaining until deadline. Green (>30 days), amber (15-30), red (1-14), flashing red (overdue). Updates in real-time via JS setInterval (client-side only, not real-time server push). "Days Remaining" or "Overdue by X days."

**2.15 — Deadline compliance dashboard widget** — On PRS analytics dashboard: doughnut chart. Segments: On Time (≤60 days), Extended (61-90 days with approved extension), Late (>90 days or >60 without extension). Center text: "XX% On Time." Year filter to see trend.

**2.16 — Deadline extension tracking** — Extension events in prs_events: record extension date, new deadline, reason, approved_by. Timeline shows extension as a branch: original deadline line, arrow to new deadline. "1 extension granted. New deadline: [date]." Case shows extension count.

**2.17 — District deadline performance** — On PRS district view: bar chart comparing each district's average resolution days. State average line. Sorted ascending (fastest first). Color: green (fast), amber, red (slow). "This district averages X days to resolve PRS cases (state avg: Y days)."

**2.18 — Deadline reminder email system** — Cron job (PHP script `dev/scripts/prs_deadline_reminders.php`): daily check for cases with deadlines within 7, 3, 1 days. Sends email to admin (configured in system_config). Email template: case list with links. "PRS Deadline Alert: X cases approaching deadline." Rate-limited: one email per day max.

#### Finding Analysis (6)

**2.19 — Finding substantiation rate chart** — Grouped bar chart: X = category, Y = % substantiated. Bars: substantiated (red), partially (amber), unsubstantiated (grey). State average for each category as horizontal reference line. Reveals which categories have highest substantiation rates.

**2.20 — Finding outcome by district** — Heatmap table: rows = districts, columns = finding outcomes (substantiated, partially, unsubstantiated). Cell color intensity = count. Sortable. "Top 10 districts by substantiated findings" filter.

**2.21 — Finding-to-corrective-action mapping** — Sankey diagram: left = finding categories, right = corrective action types (policy change, training, compensatory services, procedural change, monitoring, other). Flow width = number of cases. Reveals what actions result from what findings.

**2.22 — Corrective action compliance tracker** — Table on PRS dashboard: all corrective actions with status != completed. Columns: case number, district, action, deadline, status, days remaining. Sortable by deadline (closest first). "Overdue Actions" highlighted red. "Mark Complete" quick-action button per row.

**2.23 — Finding text analysis** — Word frequency cloud from prs_findings.finding_detail (all findings). Auto-generated via PHP: strip stopwords, count word frequency, render as CSS word cloud (sized by frequency). "Most common finding terms: IEP, evaluation, placement, FAPE, 504." Interactive: click word → filtered case list showing findings containing that word.

**2.24 — Finding impact score** — Computed metric per finding: severity (substantiated=3, partially=2, unsubstantiated=1) × corrective action complexity (policy=5, training=3, compensatory=4, procedural=2, monitoring=1). Summed per district. "Impact Score" displayed on district PRS view. Higher = more significant findings. Historical trend per district.

#### Case Relationships (6)

**2.25 — Related cases detection** — Algorithm: cases sharing same district, filed within 180 days, with overlapping categories flagged as "possibly related." "Related Cases" panel on case detail showing list with similarity score. Admin can mark as "Related" (creates prs_case_links junction table: case_id_1, case_id_2, relationship_type ENUM(related,duplicate,supersedes,appeal_of)). Manually curated.

**2.26 — Case appeal tracking** — If a PRS case is appealed (to SPR or court): parent PRS case linked via prs_case_links with relationship_type='appeal_of'. Appeal case shown on parent case detail. Appeal outcome (upheld, overturned, modified) shown. Appeal timeline integrated into parent timeline.

**2.27 — District case history summary** — On district PRS view: summary text. "[District] has had X PRS cases since 2016. Y% resulted in substantiated findings. Average resolution: Z days. Most common category: [category]. Currently N open cases." Auto-generated from SQL aggregation.

**2.28 — Complainant history** — If complainant_type = 'parent/guardian' and complainant is identified (future feature: complainant tracking), show "Other cases filed by this complainant" across districts. Requires complainant identification — placeholder until data model supports this.

**2.29 — Case-to-district-data correlation** — On case detail: "District Context" panel showing the district's restraint rate, discipline rate, SPED demographics at the time of filing. "At the time this case was filed, [District] had a restraint rate of X per 100 (state avg: Y)." Data from DESE tables for the matching school year.

**2.30 — Case resolution pattern analysis** — Auto-generated insight: "Cases in this category typically resolve in X days (this case: Y days)." "Districts with similar demographics resolved similar cases in Z days." "Only W% of [category] cases result in substantiated findings." Contextualizes the individual case within broader patterns.

---

### Phase 3: PRS Analytics & Trends (30 subphases)

#### Filing Trends (6)

**3.1 — Multi-year filing trend with forecast** — Line chart: X = year, Y = total PRS filings. Historical data 2016-2024 (solid line). Linear regression forecast 2025-2026 (dashed line). Confidence band (shaded area). Seasonal decomposition: separate lines for Q1-Q4 to show intra-year patterns. ChartJS with trendline plugin.

**3.2 — Filing rate per 1000 students** — Normalize PRS filings by district enrollment. Bar chart: X = district, Y = filings per 1000 students. State average line. Sorted descending. Reveals districts with disproportionately high complaint rates relative to size. Color: green (<state avg), amber (1-2×), red (>2×).

**3.3 — Month-over-month filing changes** — Waterfall chart: starts with previous month total, each bar = change in filings from previous month (green=increase, red=decrease), ends with current month total. X = months (Jan-Dec). Year selector. Reveals seasonal patterns.

**3.4 — New vs repeat complainant trend** — Stacked area chart: X = year, Y = filings. Stack: first-time filers (light), repeat filers (dark). Data from complainant tracking. If unavailable: show "Data not yet available — complainant tracking coming soon."

**3.5 — Filing surge detection** — Algorithm: detects months where filings exceed 2 standard deviations above the 3-year rolling average. "Surge Alert: [Month Year] had X filings (expected: Y, +Z%)." Flagged on dashboard. Possible explanations: policy change, media coverage, advocacy campaign. Admin can annotate surges with notes.

**3.6 — Category trend divergence** — Multi-line chart: each line = one PRS category's % of total filings per year. X = year, Y = % of total. Highlights shifting complaint patterns. "Special Education complaints grew from 45% to 62% of all PRS filings since 2019." Annotation callouts at inflection points.

#### Resolution Analysis (6)

**3.7 — Resolution time by category box plot** — Box-and-whisker chart: X = category, Y = days to resolution. Box shows quartiles, whiskers show range, dots show outliers. State average line per category. Identifies which categories take longest to resolve. Chart.js boxplot plugin.

**3.8 — Resolution time by district size** — Scatter plot: X = district enrollment, Y = avg resolution days. Each point = one district. Color = substantiation rate. Trend line. "Larger districts tend to have [shorter/longer] resolution times."

**3.9 — Resolution rate funnel** — Funnel chart: filed → accepted → investigated → findings → closed. Each stage shows count and % of previous stage. Width proportional to count. "X% of filed cases result in findings." "Y% of accepted cases are substantiated." Identifies where cases drop off.

**3.10 — Time-to-acceptance analysis** — Histogram: X = days from filing to acceptance. Buckets: same day, 1-7 days, 8-14, 15-30, 30+. Color by year to show trend. "Median time to acceptance: X days (improving/worsening)."

**3.11 — Investigation duration tracking** — Bar chart: average investigation days per year. X = year, Y = avg days. Overlay: number of investigators (if data available — placeholder). "Average investigation time increased from X to Y days since 2019."

**3.12 — Resolution consistency index** — Computed metric per district: standard deviation of resolution times / mean resolution time. Low = consistent, high = unpredictable. Ranked list. "Most consistent districts" and "Most variable districts." Color-coded table.

#### Status & Outcome Analysis (6)

**3.13 — Substantiation rate trend** — Line chart: X = year, Y = % of closed cases with substantiated findings. Separate lines for top 5 categories. Overall line in bold. "Substantiation rate has [increased/decreased] from X% to Y% since 2016."

**3.14 — Status distribution over time** — Stacked area chart: X = year, Y = case count. Stack segments = status (open, investigating, closed). Shows backlog — the gap between total and closed is the active caseload. "As of [date], X cases are open (Y% of total since 2016)."

**3.15 — Resolution type breakdown by year** — Grouped bar: X = year, bars grouped by resolution_type. Shows shift in outcomes over time. "Substantiated findings decreased from X% to Y% while unsubstantiated increased."

**3.16 — Case reopening rate** — Metric: % of closed cases that are later reopened (have a 'reopened' event). Displayed as a stat card with trend sparkline. "X% of closed PRS cases are later reopened." List of reopened cases with reasons.

**3.17 — Dismissal reason analysis** — Pie chart of dismissal reasons for cases with resolution_type='dismissed': outside jurisdiction, withdrawn by complainant, duplicate, insufficient information, resolved informally, other. Data from prs_events or a new dismissal_reason field.

**3.18 — Outcome prediction model** — Placeholder for ML: "Based on case characteristics (category, district demographics, complainant type), similar cases had a X% substantiation rate." Simple heuristic: look up historical substantiation rate for same category + district. Displayed as an insight on case detail.

#### District & Regional Analysis (6)

**3.19 — District PRS volume ranking** — Sortable leaderboard table: Rank, District, Total Cases, Open Cases, Substantiation Rate, Avg Resolution Days, Cases per 1000 Students. Each column sortable. Year filter. Top/bottom 10 toggle. "View District PRS Profile" link per row.

**3.20 — District PRS trend comparison** — Multi-line chart: select up to 5 districts, show each district's annual PRS filing count over time. Y = count, X = year. Each district a different color. Toggle individual lines via legend. "Normalize by enrollment" checkbox switches Y-axis to per-1000-students.

**3.21 — County-level PRS aggregation** — Reuses county infrastructure (4.21-4.30). County map colored by PRS metrics: filings per 1000, substantiation rate, avg resolution days. County rankings table. County comparison: select 2 counties, side-by-side charts. County detail page with all PRS metrics.

**3.22 — Regional (DESE region) PRS patterns** — Group districts by DESE region (Greater Boston, Northeast, Southeast, Central, Western — static mapping). Bar chart comparing regions on: filing rate, substantiation rate, resolution time. "The [Region] has the highest PRS filing rate in the state."

**3.23 — Urban vs suburban vs rural PRS patterns** — Classify districts by urbanicity (from DESE classification or enrollment-based heuristic: urban > 10K students, suburban 2K-10K, rural < 2K). Grouped bar comparing PRS metrics across these categories. "Urban districts account for X% of all PRS filings but only Y% of districts."

**3.24 — District PRS risk score** — Composite score per district: (filing rate percentile × 0.3) + (substantiation rate percentile × 0.25) + (resolution time percentile × 0.2) + (restraint rate percentile × 0.15) + (discipline rate percentile × 0.1). Higher = more concerning. Displayed as a gauge on district detail. "Risk Score: 72/100 (High)." Updated quarterly.

#### Year-over-Year Deep Dives (6)

**3.25 — YoY change heatmap table** — Table: rows = districts, columns = years. Each cell = PRS filing count. Cell color: green if decreased YoY, red if increased, intensity = magnitude. Last column: total % change from first to last year. Sortable. Quickly identifies districts with growing or declining PRS activity.

**3.26 — Category shift YoY** — Slope graph: left = category breakdown last year, right = category breakdown this year. Lines connect same category across years. Rising categories: green lines, declining: red. Line thickness proportional to magnitude. "Special Education complaints increased by X% while Civil Rights complaints decreased by Y%."

**3.27 — New district filers analysis** — Identify districts that had zero PRS cases in prior year but filed in current year. "X districts had their first PRS case this year." List with case details. "Districts with no PRS history" — highlight on map.

**3.28 — Repeat district analysis** — Districts with PRS cases every year for 5+ years. "X districts have had at least one PRS case every year since 2016." List with annual counts. "Chronic PRS districts" — what do they have in common? Compare demographics, restraint rates, discipline rates with districts with few/no PRS cases.

**3.29 — COVID impact analysis** — Pre-COVID (2016-2019) vs COVID (2020-2021) vs Post-COVID (2022-2024) comparison. Three-period grouped bar: filings, substantiation rate, resolution time. "PRS filings dropped X% during COVID but have rebounded to Y% above pre-COVID levels."

**3.30 — Legislative/policy impact annotations** — Timeline of known DESE policy changes or state legislation affecting PRS (e.g., 2022 DESE restructuring, 2024 special education reform bill). Vertical annotation lines on all trend charts. "Did [policy change] affect PRS filings?" Pre/post comparison with statistical significance test (t-test — simple PHP implementation or placeholder for manual review).

---

### Phase 4: PRS Geographic Heatmap (30 subphases)

#### Map Data Layer (6)

**4.1 — PRS map data API** — Extend map-data API (12.1) with PRS metrics: `&source=prs&metric=total_cases|filing_rate|substantiation_rate|avg_resolution_days|open_cases|risk_score`. Returns same format as district map data. Integrated into existing map page via data source selector.

**4.2 — PRS filing density map** — District SVG map colored by PRS filing rate per 1000 students. Sequential orange scale. Legend: filings/1000. Year slider. Tooltip: "[District]: X cases (Y per 1000), Z open." Click → district PRS view.

**4.3 — PRS substantiation hotspot map** — District map colored by substantiation rate. Diverging scale: green (low substantiation — fewer findings against district), red (high substantiation — more findings). "Districts with highest substantiation rates may have more systemic issues."

**4.4 — PRS resolution speed map** — District map colored by average resolution days. Diverging scale: green (fast resolution), red (slow resolution). "Slowest districts: [list]. State average: X days."

**4.5 — PRS open caseload map** — District map colored by number of currently open PRS cases. Sequential red scale. "X districts have open PRS cases. Total open cases statewide: Y." Pulse animation on districts with >5 open cases.

**4.6 — PRS risk score overlay** — District map colored by risk score (3.24). 5-color scale: green (low risk) → yellow → orange → red → dark red (high risk). "X districts flagged as high risk." List below map.

#### Map Interaction (6)

**4.7 — PRS map district click behavior** — Clicking a district on the PRS map opens a slide-out panel (reusing 17.1): district name, PRS summary stats, mini trend sparkline of PRS filings, top 3 categories, "View Full PRS Profile" button. Panel content fetched via AJAX from district PRS API.

**4.8 — PRS map multi-metric toggle** — Dropdown above map: "PRS Metric" with options from 4.1. Changing instantly recolors the map. "Compare with: [second metric]" adds a side-by-side second map (15.2 pattern).

**4.9 — PRS map year animation** — Play button animates through years (2016→2024). Map colors update per year. PRS filing count displayed as large number in corner. Speed control. Pause at any year. "Export Animation" (16.9 pattern).

**4.10 — PRS map vs DESE data overlay** — Toggle: "Overlay: Restraint Rate | Discipline Rate | SPED %." Map shows PRS data as fill color, with a pattern overlay (diagonal lines, dots) for districts where the DESE metric is above state average. Visual correlation: "Districts with high restraint rates AND high PRS filings shown with crosshatch pattern."

**4.11 — PRS map school-level overlay** — Checkbox: "Show Schools." Plots schools as dots on the map (17.2 pattern). Dot color: restraint rate. Dot size: enrollment. PRS data still colors the district background. "Schools in high-PRS districts tend to have [higher/lower] restraint rates."

**4.12 — PRS map difference view** — "Show Change: [Year A] vs [Year B]." Map colored by difference in PRS filing rate. Blue = decreased, red = increased. Legend: change range. "X districts saw PRS filings increase by >50% from [Year A] to [Year B]."

#### PRS Hotspot Analysis (6)

**4.13 — PRS hotspot detection algorithm** — Statistical method: Getis-Ord Gi* or simpler: districts with filing rate > 2σ above mean flagged as hotspots. "Hotspot districts: [list]." Red border on map. Hotspot table: district, filing rate, z-score, neighboring districts' avg.

**4.14 — PRS coldspot detection** — Districts with filing rate < 1σ below mean. "Coldspot districts: [list]." Blue border on map. "These districts have significantly fewer PRS complaints than expected." May indicate: good practices, under-reporting, or small population.

**4.15 — PRS cluster detection** — Spatial clustering: identify groups of adjacent districts all with high PRS filing rates. "The [Region] cluster includes [N] districts with a combined filing rate of X per 1000." Cluster highlighted with bold outline on map. Cluster summary stats.

**4.16 — PRS hotspot temporal stability** — Track hotspot status over time. "Districts that have been hotspots for 5+ consecutive years: [list]." "Districts that became hotspots in the last 2 years: [list]." "Districts that exited hotspot status: [list]." Table with year-by-year hotspot indicator.

**4.17 — PRS hotspot demographic profile** — Compare hotspot districts to non-hotspot districts on: enrollment, SPED %, low-income %, EL %, urbanicity, per-pupil spending. Bar chart: each metric, grouped bars for hotspot vs non-hotspot. "Hotspot districts have X% higher SPED populations than non-hotspot districts."

**4.18 — PRS hotspot intervention tracking** — If a hotspot district implements changes (from corrective actions or district events): track PRS filing rate before/after. "After implementing [change] in [year], [District]'s PRS filing rate changed from X to Y." Pre/post comparison chart. "Intervention effectiveness" score.

#### County & Regional PRS Maps (6)

**4.19 — County PRS map** — Toggle: "View by County." Aggregates PRS data to county level (4.21-4.25 pattern). County boundaries highlighted. County labels. All PRS metrics available at county level. Click county → county PRS detail.

**4.20 — County PRS comparison chart** — Horizontal bar chart: X = metric value, Y = 14 county names. Sorted descending. Color coded by region (Eastern=blue, Central=green, Western=orange). Quick visual of county rankings.

**4.21 — County PRS trend small multiples** — Grid of 14 small line charts (one per county). X = years, Y = PRS filings. Same scale across all charts for fair comparison. Highlight the selected county. Click any → zoom to full-size.

**4.22 — County PRS profile page** — New route `/counties/{slug}/prs` → County PRS tab. Shows county map, PRS stats, trend chart, district breakdown within county, top complaint categories. Compares county to state averages. "County ranks #X of 14 for PRS filing rate."

**4.23 — DESE region PRS map** — Alternative geography: 5 DESE regions instead of 14 counties. Regions: Greater Boston, Northeast, Southeast, Central, Western. Larger areas, more stable statistics. Region boundaries as SVG overlay. Region comparison charts.

**4.24 — Legislative district PRS overlay** — Overlay Massachusetts House and Senate district boundaries (SVG from MA GIS). Color each legislative district by PRS metrics of the school districts within it. "Your representative's district has X PRS cases." Useful for advocacy. Placeholder: requires legislative district boundary data.

#### Map Export & Embedding (6)

**4.25 — PRS map export as PNG** — "Download PRS Map" button. Exports current view (metric, year, zoom level) as 2× resolution PNG with legend and title. Uses existing map export infrastructure (18.2).

**4.26 — PRS map animation export as GIF** — "Export Animation" captures all years as animated GIF showing PRS filing rate evolution. Uses gif.js (18.5). Title: "PRS Filing Rate in Massachusetts: 2016-2024."

**4.27 — PRS map report PDF** — "Download PRS Map Report." Multi-page PDF: page 1 = PRS filing rate map + legend + top 10 districts table, page 2 = substantiation rate map + bottom 10 districts, page 3 = trend charts, page 4 = methodology notes. Uses print-optimized HTML → PDF via browser print.

**4.28 — PRS map embed** — "Embed this map" generates iframe code pre-configured with current PRS metric and year. Used for embedding in articles, advocacy pages, external sites. Reuses embed infrastructure (15.10, 20.2).

**4.29 — PRS map comparison export** — When in compare mode (two metrics or two years): exports a composite image showing both maps side-by-side with shared legend. Single download.

**4.30 — PRS map data table download** — "Download Map Data." Exports CSV of all districts with PRS metrics for the current map view. Includes: org_code, org_name, county, total_cases, filing_rate, substantiation_rate, avg_resolution_days, open_cases, risk_score. Uses existing CSV export infrastructure.

---

### Phase 5: PRS Cross-Reference with District Data (30 subphases)

#### Correlation Engine (6)

**5.1 — PRS-vs-restraint correlation** — Scatter plot: X = district restraint rate, Y = PRS filing rate. Each point = one district. Color = enrollment. Size = SPED %. Trend line with R-squared. "Correlation between restraint rate and PRS filings: r = X.XX ([strength])." Filter by year. Interactive: hover shows district name and both values.

**5.2 — PRS-vs-discipline correlation** — Same as 5.1 but X = discipline rate. "Districts with higher discipline rates tend to have [more/fewer] PRS complaints." Correlation coefficient displayed. Year-over-year: does the correlation hold across all years?

**5.3 — PRS-vs-demographics correlation** — Scatter plot matrix (small multiples): PRS filing rate vs SPED %, vs low-income %, vs EL %, vs attendance rate. 2×2 grid of scatter plots. Each shows correlation coefficient. Identifies which demographic factors most strongly associate with PRS activity.

**5.4 — Multi-variable regression placeholder** — "What drives PRS filings?" — simple multiple regression: PRS filing rate ~ restraint_rate + discipline_rate + sped_pct + low_income_pct + enrollment. Shows coefficients and significance. "Restraint rate is the strongest predictor of PRS filings (β = X.XX)." Displayed as a table with bar chart of coefficient magnitudes. Computed server-side via PHP statistics library or simple matrix math.

**5.5 — Lagged correlation analysis** — Does high restraint in year N predict high PRS filings in year N+1? Scatter: X = restraint rate (year N), Y = PRS filing rate (year N+1). "Restraint rate in one year predicts X% of the variation in PRS filings the following year." Time-shifted correlation.

**5.6 — Correlation stability over time** — Line chart: X = year, Y = correlation coefficient (restraint vs PRS). Shows whether the relationship is strengthening or weakening. "The correlation between restraint rate and PRS filings has [increased/decreased] from r=X.XX in 2016 to r=Y.YY in 2024."

#### Cross-Reference Dashboards (6)

**5.7 — District cross-reference profile** — On district detail: "PRS & Data Cross-Reference" tab. Side-by-side panels: PRS metrics (filings, substantiation rate, resolution days) next to DESE metrics (restraint rate, discipline rate, SPED %). Visual connector lines showing correlations. "This district's PRS filing rate is X (state avg: Y). Its restraint rate is Z (state avg: W)."

**5.8 — Cross-reference scatter with quadrant labels** — Scatter plot (5.1) divided into 4 quadrants: top-right = high PRS + high restraint ("Troubled"), top-left = low PRS + high restraint ("Under-reported?"), bottom-right = high PRS + low restraint ("Responsive"), bottom-left = low PRS + low restraint ("Low Concern"). Quadrants labeled. District names in each quadrant listed.

**5.9 — Cross-reference leaderboard** — Sortable table: District, PRS Filing Rate, Restraint Rate, Discipline Rate, Attendance Rate, SPED Grad Rate, Risk Score. Each column has a mini bar chart (data bars) inside the cell proportional to the value. Conditional formatting: top 10% green, bottom 10% red. "Sort by any column."

**5.10 — Cross-reference trend dashboard** — Small multiple line charts (3×2 grid): each chart shows one district's PRS filing rate (orange line) and restraint rate (blue line) over time. Dual y-axis. "Are PRS and restraint moving together?" 6 districts shown per page, paginated.

**5.11 — PRS outlier cross-reference** — Flag districts that are outliers in both PRS and DESE metrics. "X districts are in the top 10% for both PRS filings AND restraint rate." Red highlight. "Y districts have high PRS but low restraint — possible proactive complainants or responsive districts." Amber highlight.

**5.12 — "If this district, then that outcome" insights** — Auto-generated rules: "Districts with restraint rate > X per 100 have a Y% chance of above-average PRS filings." "Districts with SPED grad rate < Z% have W× the state average substantiation rate." Decision tree or rule list displayed in a card. Generated by simple threshold analysis.

#### District PRS Data Integration (6)

**5.13 — District detail PRS tab** — New tab on `/districts/{code}`: "PRS Activity." Shows: total PRS cases, open cases, filing trend sparkline, category breakdown mini pie, recent cases list (last 5), "View All PRS Cases" link. Styled consistently with existing tabs (Restraint, Demographics, SPED Outcomes).

**5.14 — District detail PRS timeline embed** — In the PRS tab: mini timeline showing the 5 most recent PRS case events for the district. Compact version of the full timeline (2.7). "Latest PRS activity" header. Each event = dot + date + case number link.

**5.15 — District detail PRS comparison to state** — In the PRS tab: "How does [District] compare?" Horizontal bar gauges: PRS filing rate (this district vs state avg), substantiation rate, avg resolution days. Color: green if better than state avg, red if worse. Peer percentile (4.19 pattern).

**5.16 — District detail PRS vs peers** — "Similar districts (by enrollment and demographics) average X PRS cases per year. This district: Y cases." Bar chart: this district highlighted in orange among grey peer district bars. "This district ranks #X of Y similar districts for PRS filing rate."

**5.17 — School detail PRS context** — On school detail page: "This school's district has X PRS cases." Link to district PRS view. "PRS cases in this district most commonly involve: [top 3 categories]." Contextualizes the school within district-level advocacy activity.

**5.18 — County detail PRS section** — On county detail page: PRS section showing county-level PRS metrics, district breakdown within county, county rank among 14 counties. Charts: PRS filing trend, category breakdown, substantiation rate vs state avg.

#### Combined Analytics Views (6)

**5.19 — PRS + DESE combined dashboard** — New route `/data/combined` → combined analytics page. 3-column layout: left = PRS metrics, center = DESE metrics, right = correlation insights. Each column has its own stat cards and mini charts. "Combined District Risk" leaderboard at bottom. Year selector syncs both sides.

**5.20 — Combined metric radar chart** — On combined dashboard: radar chart with axes: PRS Filing Rate, Restraint Rate, Discipline Rate, Attendance Rate, SPED Grad Rate, Chronic Absenteeism. One polygon per selected district (up to 5). "Holistic district performance view." Color per district. Toggle axes on/off. Invert axes where lower = better (PRS, restraint, discipline, absenteeism).

**5.21 — Combined trend correlations over time** — Animated chart: X = year, left Y = PRS filing rate (orange), right Y = restraint rate (blue). Lines move together or diverge. "PRS filings and restraint rates show [convergent/divergent] trends." Year slider or auto-play.

**5.22 — Combined district ranking composite** — New ranking: "Overall District Accountability Score." Formula: (100 − PRS filing rate percentile) × 0.3 + (100 − restraint rate percentile) × 0.25 + (100 − discipline rate percentile) × 0.2 + attendance rate percentile × 0.15 + SPED grad rate percentile × 0.1. Higher = better across all dimensions. Top 10 and bottom 10 shown. "Score: X/100."

**5.23 — Combined year-over-year change matrix** — Table: rows = districts, columns = YoY change in PRS filings, restraint rate, discipline rate, attendance. Each cell = arrow (up/down) with % change and color (green=improved, red=worsened). "X districts improved across all metrics. Y districts worsened across all."

**5.24 — Combined "story finder"** — Auto-generated narrative insights: "In 2023-24, [District] saw PRS filings increase by X% while restraint rates decreased by Y% — suggesting complaints may be addressing issues before they result in restraints." Or: "[District] has both rising PRS filings AND rising restraint rates — a concerning trend." One insight per district, surfaced on combined dashboard. Generated by rule-based pattern matching on the data.

#### Export & API for Cross-Reference (6)

**5.25 — Cross-reference data API** — `/api/prs/cross-ref?district=X&metrics=restraint_rate,discipline_rate&years=2019-2024`. Returns JSON with PRS metrics and DESE metrics side-by-side per year. Used by all cross-reference charts. Cached 300s.

**5.26 — Combined district report export** — "Download Full District Report." Multi-page PDF per district: PRS summary, DESE data summary, trends, peer comparison, correlation insights. Generated server-side or client-side print. All charts included as images. Cover page with district name and report date.

**5.27 — Combined data CSV export** — "Download Combined Dataset." CSV with columns: org_code, org_name, year, prs_filings, prs_substantiation_rate, prs_avg_resolution_days, restraint_rate, discipline_rate, attendance_rate, sped_grad_pct, low_income_pct, enrollment. One row per district per year. All public data in one file. Research-ready.

**5.28 — Cross-reference API documentation** — Section in `/data/help`: documents cross-reference API with example requests and responses. Explains correlation methodology, quadrant analysis, composite scoring. Links to DESE data definitions and PRS documentation.

**5.29 — Cross-reference embeddable widgets** — "Embed this comparison" generates iframe: small scatter plot showing selected district vs state for PRS vs restraint. Embeddable in articles. Reuses embed infrastructure.

**5.30 — Cross-reference data freshness** — Combined data pulls from both PRS tables and DESE tables. "PRS data last updated: [date]. DESE data last updated: [date]." Warning banner if either dataset is > 1 year old. "Data currency affects correlation accuracy."

---

### Phase 6: PRS Document & Finding Linking (30 subphases)

#### Document Management (6)

**6.1 — PRS document upload** — On PRS case detail: "Upload Document" button. Opens modal: file input (PDF, DOCX, XLSX, EML, images — max 50MB), document type dropdown (complaint, district_response, finding_letter, evidence, correspondence, corrective_action_plan, compliance_report, other), document date, description textarea. Uploads to `documents` table via existing upload infrastructure. Auto-creates document_links row linking to this PRS case.

**6.2 — PRS document batch upload** — "Upload Multiple Documents." File input accepts multiple files. Each file gets a row in the upload queue. User sets type and date per file (or bulk-set for all). Progress bar per file. "Process X files" button. All linked to the case on completion.

**6.3 — PRS document viewer integration** — Clicking a document in the linked documents panel opens the existing document viewer (`/documents/{id}`) in a modal or new tab. Viewer shows: file metadata, extracted text (body_text), OCR text if available, download link. "Open in New Tab" link.

**6.4 — PRS document OCR trigger** — For uploaded image-based PDFs: "This document may contain scanned text. Run OCR?" Button triggers OCR pipeline (if configured) or flags for admin review (adds to document_workflow with ocr_required=1). Status: "OCR Pending | Processing | Complete."

**6.5 — PRS document classification auto-suggest** — When uploading a document, suggest doc_family='prs' and doc_subtype based on the selected document_type. "Based on type 'Finding Letter', this will be classified as Determination / finding_letter." User can override. Consistency with existing document taxonomy.

**6.6 — PRS document deduplication** — On upload: check file_hash against existing documents. "This file may already exist in the system (matched by SHA-256)." Show existing document link. "Upload anyway" or "Link existing document instead." Prevents duplicate storage.

#### Finding-to-Document Linking (6)

**6.7 — Finding document attachment** — On each finding card (2.4): "Attach Document" button. Opens modal to upload or search existing documents. Linked via document_links with link_type='finding' and target_type='prs_finding', target_id=finding.id. Documents appear below the finding card. "Supporting Documents (N)."

**6.8 — Finding document evidence strength** — For each linked document: "Evidence Strength" dropdown (admin): strong, moderate, weak, informational. Color-coded icon on the document link. "Strong evidence documents are cited in the finding." Helps prioritize document review.

**6.9 — Finding-to-document cross-reference view** — "Documents by Finding" view: table with rows = findings, columns = linked documents. Checkmarks show which documents support which findings. "This document supports X of Y findings." Helps see document coverage.

**6.10 — Finding document timeline integration** — Linked documents appear on the case timeline (2.11) at their document_date. Documents linked to a specific finding show the finding number as a badge on the timeline dot. "Finding #2 supported by [document name] (filed [date])."

**6.11 — Finding document full-text search** — Search box on case detail: "Search within case documents." Full-text search across body_text of all documents linked to this case. Returns snippets with highlighted search terms. "X results in Y documents." Filters by document type.

**6.12 — Finding document export bundle** — "Download All Case Documents" button: generates a zip file of all documents linked to this case. Includes an index PDF listing all documents with metadata. Server-side zip generation via PHP ZipArchive. Progress bar for large cases.

#### Finding Analysis Tools (6)

**6.13 — Finding text search across all cases** — New search scope: "Search PRS Findings." Full-text search across prs_findings.finding_detail. Returns: finding text snippet (with highlight), case number, district, category, finding result. "X findings match '[search term]'." Links to the case detail page scrolled to the finding.

**6.14 — Finding similarity detection** — Algorithm: compare new finding text to all historical findings using TF-IDF or simple term frequency. "Similar findings in other cases: [list with similarity %]." Helps identify patterns. "This finding is very similar to findings in PRS-2023-0045 and PRS-2022-0089." Admin tool for consistency checking.

**6.15 — Finding citation network** — Graph visualization: nodes = PRS cases (or findings), edges = "cites similar regulation" or "similar finding text." Force-directed layout (D3.js from CDN). Click node → case detail. "Regulation 603 CMR 28.00 cited in X findings across Y cases." Centrality metrics highlight the most-referenced regulations.

**6.16 — Finding outcome predictor** — Based on finding category, district history, and document evidence strength: simple heuristic estimating likelihood of substantiation. "Based on similar findings, estimated substantiation probability: X%." Displayed as a gauge on finding detail. "Actual outcome: [substantiated/unsubstantiated]" after resolution for accuracy tracking.

**6.17 — Finding corrective action effectiveness** — Track whether corrective actions are completed on time and whether similar findings recur in the same district. "After [corrective action] in [year], similar findings [did/did not] recur." Effectiveness score: 0-100. "This district has a X% corrective action compliance rate."

**6.18 — Finding-to-regulation mapping** — Create `prs_regulations` lookup table: regulation_citation, title, description, url. Auto-detect regulation citations in finding_detail text (regex: `\d{3}\s*CMR\s*\d{2,3}\.\d{2,3}`). Link findings to regulations via prs_finding_regulations junction table. "Regulation 603 CMR 28.05 cited in X findings." Regulation detail page showing all cases citing it.

#### Document Analytics (6)

**6.19 — Document type distribution per case** — Bar chart on case detail: X = document type, Y = count. "This case has X documents: Y findings, Z pieces of evidence, W correspondence."

**6.20 — Document timeline density** — Histogram: X = months relative to filing date (-3 to +18 months), Y = number of documents filed in that month. Peak months identified. "Document activity peaks at month X (typically when findings are issued)."

**6.21 — Document-to-resolution correlation** — Scatter plot: X = number of documents in case, Y = resolution days. Each point = one case. "Cases with more documents take [longer/shorter] to resolve." Correlation coefficient. "Average case has X documents. Cases with >Y documents take Z% longer."

**6.22 — Missing document detection** — Rule-based: for cases with status='findings' but no document of type 'finding_letter', flag "Expected: Finding Letter — not yet uploaded." For cases with status='closed' and resolution='substantiated' but no 'corrective_action_plan', flag similarly. Admin checklist on case detail.

**6.23 — Document OCR quality report** — List documents with ocr_required=1 and no ocr_text. "X documents need OCR processing." Table: document name, case, upload date. "Process All" button triggers batch OCR. Admin dashboard widget.

**6.24 — Document access audit log** — Track who views which PRS documents (admin only, if session tracking extended). "Document [name] viewed by [user] at [datetime]." Audit log filtered by document. Privacy: only admin users' access logged. "Accessed X times."

#### Integration with Main Document System (6)

**6.25 — Unified document search with PRS filter** — Extend main search to filter by doc_family='prs'. "Search all PRS documents." Results show document title, linked case, date, type. Click → document viewer or case detail.

**6.26 — PRS documents on district document list** — District detail page: "Documents" tab shows all documents where org_id = district AND doc_family = 'prs'. "X PRS-related documents for this district." Chronological list. "Upload PRS Document" button pre-fills org_id and doc_family.

**6.27 — PRS document workflow integration** — PRS documents flow through the standard document_workflow pipeline: ingested → classified → reviewed → published. Admin can set is_public on PRS documents (default: false — internal only). "Publish" makes the document visible on the public case detail page.

**6.28 — Bulk document linking tool** — Admin tool: "Link Documents to PRS Cases." Search for unlinked documents with doc_family='prs', suggest matching cases by org_id + date proximity. Checkbox per suggestion. "Link Selected" button. "X unlinked PRS documents found."

**6.29 — Document retention policy** — PRS documents: retain indefinitely (legal requirement). Displayed on document detail: "Retention: Permanent (PRS case record)." Other document types may have different policies (configurable in system_config).

**6.30 — PRS document export for legal/discovery** — "Export Case File" button: generates a zip with all documents + a PDF index + a CSV log of all case events + findings summary. Designed for legal discovery or SPR appeals. Includes "Prepared for [purpose] on [date]" cover sheet.

---

### Phase 7: PRS Advanced Visualizations (30 subphases)

#### Workflow Visualizations (6)

**7.1 — PRS case lifecycle Sankey diagram** — Full Sankey: filing → acceptance → assignment → investigation → preliminary findings → district response → final findings → corrective action → compliance verification → closure. Branch widths = case counts. Color gradient from blue (filing) through amber (investigation) to green (closure). Hover segment → count and %. "Bottleneck: X% of cases stall at [stage]." Click segment → filtered case list. Uses D3.js Sankey (loaded from CDN).

**7.2 — PRS case duration waterfall** — Horizontal waterfall: each stage shown as a horizontal bar, positioned at its start date, width = duration. All cases for a selected district or category overlaid as semi-transparent bars. Median case shown as bold line. "Median time at [stage]: X days." Identifies which stage consumes the most time.

**7.3 — PRS workload animation** — Animated bubble chart: X = time (animating through months), Y = nothing (all at same level), bubble size and color = number of active cases per district. Bubbles merge/split as cases open/close. Play/pause. "Cumulative caseload over time." Shows total open cases at any moment.

**7.4 — PRS process flow diagram** — Static SVG flowchart: boxes = stages, arrows = transitions, labels on arrows = % of cases taking this path, arrow thickness = proportion. Generated server-side as SVG. "X% of cases go directly from investigation to findings. Y% are resolved before findings." Alternative paths visible (withdrawn, dismissed).

**7.5 — PRS case complexity indicator** — Computed metric per case: complexity = (number of allegations × 2) + (number of findings × 3) + (number of documents × 1) + (number of events × 0.5) + (days open / 30). Displayed as a 1-10 scale on case detail. "Complexity: 7/10 (High)." Color: green (simple), amber, red (complex). Used to prioritize review.

**7.6 — PRS workload balance chart** — If investigator/assignee data available: bar chart showing cases per investigator. "Caseload distribution." Average line. "X investigators have above-average caseloads." Placeholder: requires investigator assignment tracking.

#### Statistical Visualizations (6)

**7.7 — PRS filing forecast** — Time series decomposition: trend component, seasonal component, residual. X = year, Y = filings. Holt-Winters or simpler moving average forecast for next 2 years. Confidence intervals. "Predicted 2025 filings: X (range: Y-Z)."

**7.8 — PRS statistical process control chart** — X = time (months), Y = filings. Center line = mean. Upper/lower control limits = ±3σ. Points outside limits flagged. "X months exceeded the upper control limit — indicating special cause variation." Auto-detected with annotations.

**7.9 — PRS category affinity analysis** — Co-occurrence matrix: which categories appear together in the same case. Heatmap: rows = categories, columns = categories, cell color = number of cases with both. "SPED + Discipline co-occur in X% of cases." Network graph alternative.

**7.10 — PRS district clustering** — K-means clustering (k=5) of districts based on PRS metrics + DESE metrics. Each cluster profiled: "Cluster 1 (N districts): high PRS, high restraint, urban, low-income. Cluster 2 (N districts): low PRS, low restraint, suburban, affluent." Scatter plot colored by cluster. Cluster profiles as cards.

**7.11 — PRS survival analysis** — Kaplan-Meier curve: X = days since filing, Y = % of cases still open. Separate curves for each category or district. "Median survival time (time until 50% of cases closed): X days." "Category A cases resolve faster than Category B (p < 0.05)." Statistical test: log-rank test (simple PHP implementation).

**7.12 — PRS Benford's Law analysis** — Apply Benford's Law to PRS filing counts per district per year. "Expected distribution vs actual." Detects potential under-reporting or data anomalies. "X districts deviate significantly from expected — possible data quality issues." Flagged for admin review.

#### Calendar & Timeline Visualizations (6)

**7.13 — PRS calendar heatmap** — Full-year calendar view (12 months in a grid). Each day colored by: number of PRS cases filed that day (sequential), or if any case had a deadline that day (red dot). Year selector. Click day → list of cases. "Busiest month: [Month] (X filings)." Interactive tooltip per day.

**7.14 — PRS Gantt chart per district** — For a selected district: horizontal bars showing each PRS case. Bar starts at filing_date, ends at closure_date (or present). Color = current status. Bars stacked vertically by filing date. "X cases currently open." Hover bar → case number + title. Click → case detail.

**7.15 — PRS timeline spiral** — Creative visualization: spiral chart where each loop = one year, angle = month, distance from center = number of filings. Seasons visible as clusters. "PRS filings consistently spike in [month]." Archimedean spiral rendered via SVG path. Novelty visualization — secondary to standard charts.

**7.16 — PRS event sequence analysis** — Horizontal stacked timeline: each row = one case. X = time (months from filing). Colored segments = event types. Cases sorted by total duration. "Pattern: X% of cases follow the sequence: filed → accepted → investigating → findings." Sequence mining algorithm identifies common patterns.

**7.17 — PRS deadline calendar** — Month-view calendar showing all upcoming PRS deadlines. Each deadline = colored dot on the date. Color: green (>30 days), amber (15-30), red (<15). Click date → list of cases with deadlines. "Next 30 days: X deadlines." Filter by district, category. Admin tool for workload planning.

**7.18 — PRS timeline comparison between districts** — Two timelines side-by-side for selected districts. Same scale. "District A median resolution: X days. District B: Y days." Visual comparison of case durations. "District A resolves cases X% faster."

#### Narrative & Insight Visualizations (6)

**7.19 — PRS "year in review" infographic** — Auto-generated infographic page: key stats, top charts, notable trends, highlights. Designed for social sharing. "2024 PRS Year in Review: X cases filed, Y% substantiated, top category: [category]." Single-page printable. Updated annually.

**7.20 — PRS case "at a glance" summary card** — Compact card for embedding in articles or dashboards: PRS number, district, status, key dates, finding summary (1-2 lines). "Quick View" → expands to show full timeline. Used in cross-reference dashboards and district pages.

**7.21 — PRS narrative generator** — Auto-generate paragraph summarizing a district's PRS history: "[District] has had X PRS cases since 2016. The most common category is [category] (Y% of cases). Z% of cases resulted in substantiated findings. The district's PRS filing rate is [above/below] the state average. Notable case: [case title] ([year])." Displayed on district PRS view. Regenerated when data changes.

**7.22 — PRS data story templates** — Curated "stories" with pre-built charts: "The Rise of Special Education Complaints", "Which Districts Have the Most PRS Activity?", "Does Restraint Drive PRS Complaints?", "COVID's Impact on PRS Filings." Each story = a page with narrative text interspersed with live charts. Template populated with current data. Updated on each data refresh.

**7.23 — PRS trend alert system** — Automated alerts: "PRS filings in [category] have increased X% this quarter compared to last." "District [name] had Y PRS cases this year — its highest ever." "Statewide substantiation rate dropped to X% — lowest since 2016." Alerts displayed on dashboard. Configurable thresholds. "Dismiss" or "Investigate" per alert.

**7.24 — PRS public data transparency report** — Annual report page: "Massachusetts PRS Transparency Report." All aggregated stats, no individual case details. Methodology section. "Data last updated: [date]. Source: DESE PRS database via public records." Downloadable as PDF. Designed for public dissemination.

#### Interactive Exploration Tools (6)

**7.25 — PRS data drill-down explorer** — Interactive hierarchical explorer: start with statewide overview → click county → click district → click case. Each level shows appropriate charts and stats. Breadcrumb navigation. "Current view: [County] → [District]." URL updates at each level for sharing/bookmarking.

**7.26 — PRS "ask a question" natural language** — Simple query interface: dropdowns for "Show me [metric] for [district/category/year] compared to [state average/peer group]." Builds the appropriate chart. "Show me PRS filing trends for Boston compared to state average." Maps to API calls. No AI — just a structured query builder.

**7.27 — PRS custom chart builder** — "Build Your Own Chart." Select: chart type, X-axis field, Y-axis field, filter criteria, color scheme. Preview updates live. "Save Chart" adds to a personal dashboard (localStorage). "Share Chart" generates URL. Admin: "Publish Chart" adds to public analytics page.

**7.28 — PRS data annotation system** — Admin can add annotations to any chart: click a data point, add note. "This spike corresponds to the DESE restructuring announcement (March 2023)." Annotation stored in `chart_annotations` table (chart_id, data_point, text, created_by, created_at). Visible to all users as a dotted line + tooltip. Public annotations vs admin-only.

**7.29 — PRS chart embed system** — Every chart has an "Embed" button. Generates an iframe or static image embed code. "Embed this chart on your website." iframe loads a minimal page with just the chart + data source. Static image is a pre-rendered PNG (cached 24h). Used by advocacy organizations.

**7.30 — PRS data download center** — Central page `/prs/data`: all downloadable datasets listed with descriptions, file sizes, last updated dates. "Complete PRS Case Database (CSV)", "PRS District Summary (CSV)", "PRS Category Trends (CSV)", "PRS + DESE Combined Dataset (CSV)". "Download All" button. Request form for custom data extracts (emailed when ready).

---

### Phase 8: PRS Comparison & Ranking (30 subphases)

#### District Rankings (6)

**8.1 — PRS volume ranking** — Sortable table of all districts ranked by total PRS cases. Columns: Rank, District, Total Cases, Open Cases, Cases per 1000 Students, YoY Change. Rank change indicator (up/down arrow + positions moved). Year filter. "Top 25" and "Bottom 25" quick filters. District names linked to district PRS view.

**8.2 — PRS substantiation rate ranking** — Sortable table ranked by substantiation rate (highest = most findings against district). "Districts with Highest Substantiation Rates." Caveat note: "High substantiation rates may indicate systemic issues OR effective DESE investigation. Consider alongside filing rates." Green-to-red color scale.

**8.3 — PRS resolution speed ranking** — Table ranked by average resolution days (fastest first). "Fastest-Resolving Districts" and "Slowest-Resolving Districts." State average benchmark. "District X resolves PRS cases in an average of Y days (state avg: Z)."

**8.4 — PRS improvement ranking** — "Most Improved Districts" — ranked by decrease in PRS filing rate from earliest to latest year. "District X reduced PRS filings by Y% since 2016." Green ribbon icon. "Least Improved" / "Worsened" — ranked by increase.

**8.5 — PRS "no cases" honor roll** — List of districts with zero PRS cases in the selected year (or all years). "X districts had no PRS cases in [year]." May indicate: small size, good practices, or under-awareness of PRS process. Contextual note: "Districts with < 500 students excluded from some comparisons."

**8.6 — PRS category-specific rankings** — Dropdown: "Rank by Category." Select a PRS category, rankings update to show only cases in that category. "Top districts for Special Education PRS complaints." Makes rankings more specific and actionable.

#### Peer Group Comparisons (6)

**8.7 — Peer group definition** — Districts grouped by enrollment tier: Small (<1,000), Medium (1,000-3,000), Large (3,000-10,000), Very Large (>10,000). Or by DESE accountability classification. Peer group shown on district PRS view. "Comparing [District] to other Large districts."

**8.8 — Within-peer-group ranking** — "Your district ranks #X of Y [peer group] districts for PRS filing rate." Peer group comparison table: only districts in the same tier shown. More relevant than statewide ranking for small districts.

**8.9 — Peer group average overlay** — On every district PRS chart: add a dashed line showing the peer group average. "Peer group average: X. Your district: Y." Color: grey dashed line. Easy visual comparison to similar districts.

**8.10 — Peer group distribution chart** — Violin plot or box plot: shows distribution of PRS metrics across the peer group. Your district's position marked with an orange dot. "Your district is at the [Xth] percentile within its peer group."

**8.11 — Cross-peer-group comparison** — Bar chart comparing average PRS metrics across peer groups. "Large districts average X PRS cases per year. Small districts average Y." "Per-student rate: Small districts have [higher/lower] rates than Large." Normalized comparison.

**8.12 — Peer group recommendation** — "Districts similar to yours that have lower PRS filing rates: [list]." "What are they doing differently?" Links to those districts' profiles. Auto-generated suggestion based on peer group comparison.

#### Temporal Rankings (6)

**8.13 — Historical ranking tracker** — For a selected district: line chart showing its rank over time. X = year, Y = rank (lower = better). "Your district's PRS filing rate rank: 2016: #X, 2024: #Y. [Improved/Worsened] by Z positions."

**8.14 — Ranking mobility matrix** — Transition matrix: rows = rank quartile in year N-1, columns = rank quartile in year N. Cell = number (or %) of districts that moved from one quartile to another. Color intensity. "X% of districts stayed in the same quartile. Y% moved up." Reveals ranking stability.

**8.15 — "Biggest risers and fallers"** — Top 10 districts that moved the most rank positions year-over-year. "District X moved from #Y to #Z ([+/-]N positions)." Brief explanation if possible: "Corresponds to [event]." Table with sparklines showing rank trajectory.

**8.16 — Consecutive year rankings** — "Districts in the top 10 for PRS filings for [N] consecutive years." "Districts in the bottom 10 for [N] consecutive years." Streak tracking. Color-coded: red (persistent top 10 — concerns), green (persistent bottom 10 — positive).

**8.17 — Year-over-year rank correlation** — Scatter plot: X = rank in year N-1, Y = rank in year N. Each point = one district. Diagonal line = no change. Points above diagonal = improved (lower rank number = better). "Rank correlation: r = X.XX (strong stability / high mobility)."

**8.18 — Long-term ranking trends** — For each district: slope of the rank trend line over all years. "Districts with the steepest improvement trajectory" and "Districts with the steepest decline." Table sorted by slope. Color-coded.

#### Composite Rankings & Scoring (6)

**8.19 — PRS composite score formula** — Document the exact formula: Composite = (Z-score of filing_rate × -0.3) + (Z-score of substantiation_rate × -0.25) + (Z-score of resolution_days × -0.2) + (Z-score of restraint_rate × -0.15) + (Z-score of discipline_rate × -0.1). All metrics inverted so higher composite = better. Z-score normalizes across different scales. Formula displayed on rankings page for transparency.

**8.20 — PRS composite score ranking** — Table ranked by composite score. "Healthiest Districts (low PRS, low restraint, fast resolution)" and "Most Concerning Districts." Composite score shown as a gauge bar (0-100). Color gradient: green→red.

**8.21 — Composite score trend** — Line chart: composite score over time per district. "Is [District] improving or declining overall?" Multi-line for comparing multiple districts. Year-over-year change indicator.

**8.22 — Composite score decomposition** — Waterfall chart per district: shows how each component contributes to the composite score. "Restraint rate contributes most to [District]'s low score." "PRS filing rate improved, but discipline rate worsened — net change: +X." Makes the composite transparent and actionable.

**8.23 — Weighted vs unweighted comparison** — Toggle on rankings: "Equal Weighting" vs "Default Weighting" vs "Custom Weighting." Sliders to adjust component weights. "Advocacy Weighting" preset (PRS × 0.5, Restraint × 0.3, Discipline × 0.2). Rankings update in real-time. Custom weighting stored in URL for sharing.

**8.24 — Ranking robustness check** — "How much do rankings change with different weights?" Sensitivity analysis: show top 10 under 3 different weighting schemes. "Rankings are [robust/sensitive] to weighting changes." Note on rankings page for transparency.

#### Ranking Visualizations (6)

**8.25 — Ranking slope graph** — Left = rank in earliest year, right = rank in latest year. Lines connect same district. Rising lines (green) = improved, falling (red) = worsened. Thick lines for large changes. "Most districts [improved/worsened] over this period."

**8.26 — Ranking bump chart** — Similar to slope graph but for all intermediate years. Rank on Y-axis (inverted), years on X-axis. Lines criss-cross. Interactive: hover to highlight one district's path. "District X's rank over time: [#1 (2016) → #2 (2018) → #1 (2020) → #3 (2024)]."

**8.27 — Ranking lollipop chart** — Horizontal lollipop: circle at each district's composite score position, line from circle to zero. Sorted descending. Color by peer group. "Top 25 districts by composite score." Clean, publication-ready.

**8.28 — Ranking radar chart for top/bottom districts** — Radar chart comparing #1 ranked district vs #50 vs #427 (last). Axes = all component metrics. Visual profile of "what a top-ranked district looks like" vs bottom. "Top districts excel at: [metrics]. Bottom districts struggle with: [metrics]."

**8.29 — Ranking small multiples** — Grid of small bar charts, one per peer group. Each chart: top 5 and bottom 5 districts in that peer group. Compact, scannable. "At a glance: who leads and lags in each peer group."

**8.30 — Ranking export & sharing** — "Download Rankings" CSV. "Share This Ranking" URL with all current filters and sort order encoded. "Embed This Ranking" iframe. Rankings page has social sharing meta tags. Reuses existing export infrastructure.

---

### Phase 9: PRS Export, Alerts & Automation (30 subphases)

#### Report Generation (6)

**9.1 — District PRS report** — Multi-page report per district: page 1 = summary (total cases, substantiation rate, resolution time, trends), page 2 = case list (all cases with key details), page 3 = comparison to state and peer group, page 4 = individual case details (selected high-profile cases). Generated as HTML → print to PDF. Download button on district PRS view.

**9.2 — Statewide annual PRS report** — Comprehensive annual report: executive summary, state-level trends, category breakdowns, district rankings, geographic analysis, year-over-year changes, methodology. 20+ pages. Auto-generated from current data. Updated annually (or on-demand). Download as PDF. "2024 Massachusetts PRS Annual Report."

**9.3 — Custom date range report** — "Generate Report: [From Date] to [To Date]." Filters all data to the specified range. Same structure as standard reports but for custom periods. "Q1 2024 PRS Report." Useful for board meetings, advocacy campaigns.

**9.4 — Category-specific report** — "Generate Report: Special Education PRS Cases." All analytics filtered to a single category. "2024 Special Education PRS Report." Includes: category trend, top districts for this category, substantiation rate for this category, comparison to other categories.

**9.5 — Report template system** — Admin page: manage report templates. Each template defines: sections, chart types, data filters, branding (logo, colors, header/footer text). "Create Template" → name + configuration. "Generate Report from Template" → pick template + parameters → generate PDF. Templates stored in system_config as JSON.

**9.6 — Report delivery** — "Email this report" form: recipient email, optional message, format (PDF/HTML). Sends report as attachment. "Schedule this report" (placeholder for future cron): daily/weekly/monthly generation and email. Stores in report_subscriptions table.

#### Data Export (6)

**9.7 — PRS case CSV export** — "Export Cases (CSV)." Downloads all PRS cases matching current filters as CSV. Columns: all prs_cases fields (except JSON/arrays). Includes computed fields (days_open, deadline_status). UTF-8 BOM for Excel. Date format: YYYY-MM-DD. Uses existing CSV infrastructure.

**9.8 — PRS events export** — "Export Case Timeline (CSV)." Downloads all events for selected cases. Columns: case_number, event_date, event_type, description, actor. Enables external timeline analysis.

**9.9 — PRS findings export** — "Export Findings (CSV)." All findings with case context. Columns: case_number, district, category, finding, detail, regulation, corrective_action. Research-ready format.

**9.10 — PRS full data dump** — "Download Complete PRS Dataset." Single ZIP containing: cases.csv, events.csv, findings.csv, documents_manifest.csv, README.txt (data dictionary). All public data. Generated on-demand, cached for 24h. "Preparing download... [progress]."

**9.11 — PRS API data access** — Document all PRS API endpoints in `/data/help#prs-api`. Provide copy-paste curl examples. "Access PRS data programmatically." Rate limit: 100 requests/minute per IP (configurable). API key for higher limits (future).

**9.12 — PRS data in Excel format** — "Export as Excel (.xlsx)." Generates a formatted Excel file with multiple sheets (Cases, Events, Findings, Summary). Column widths auto-sized. Header row bold with filters. Uses PhpSpreadsheet library (or simple XLSX writer). Fallback: CSV if library unavailable.

#### Alerts & Notifications (6)

**9.13 — New PRS case alert** — When a new PRS case is added (via admin or import): check if the district has above-average PRS activity. If yes, flag: "New PRS case in high-activity district: [District] — [case number]." Dashboard alert + optional email. Configurable per user.

**9.14 — PRS deadline alerts** — Daily check: cases approaching statutory deadline. Dashboard widget: "X cases with deadlines in the next 7 days." Email digest (daily at 8am): list of approaching deadlines. Admin configurable: alert thresholds (7 days, 3 days, 1 day, overdue).

**9.15 — PRS trend anomaly alert** — Statistical detection: when monthly PRS filings exceed 2σ above 12-month rolling average. "Anomaly Detected: [Month] had X filings (expected: Y, +Z%)." Dashboard alert. "Investigate" button links to filtered case list.

**9.16 — PRS district watchlist alert** — Admin can add districts to a "Watchlist." When a watchlist district has a new PRS case, deadline approaching, or finding issued: alert generated. "Watchlist Alert: [District] — new finding issued in PRS-[number]." Email + dashboard. Watchlist managed in admin panel.

**9.17 — PRS category surge alert** — When a specific PRS category's monthly filings spike: "Category Alert: [Category] filings increased X% this month." Possible causes: policy change, media coverage, advocacy campaign. Dashboard alert with "View trend" link.

**9.18 — PRS alert digest** — Weekly email digest: "PRS Weekly Digest — [date range]." Sections: New Cases (X), Closed Cases (Y), Approaching Deadlines (Z), Anomalies Detected (W), Watchlist Updates (V). Each item links to the relevant page. Opt-in. Configurable: daily or weekly.

#### Automation (6)

**9.19 — Automated PRS data refresh** — Cron job (`dev/scripts/prs_refresh.php`): checks DESE PRS database for new/updated cases (placeholder — requires DESE data source connection). Updates prs_cases, adds new events. Logs changes to sync_log. "Last refreshed: [datetime]. New cases: X. Updated cases: Y." Admin button: "Refresh Now."

**9.20 — Automated deadline recalculation** — Triggered when a new event is added to a case: recalculate all deadline fields (statutory_deadline, extended_deadline, days_open, etc.). PHP event listener or explicit call in the event save handler. "Deadlines recalculated." Audit log records recalculation.

**9.21 — Automated finding-to-regulation linking** — On finding save: scan finding_detail for regulation citations (regex). Auto-create prs_finding_regulations rows. "X regulations auto-detected." Admin can review and correct. Flagged if no regulations detected: "No regulation citations found — manual review recommended."

**9.22 — Automated duplicate detection** — Weekly cron: scan for potential duplicate cases (1.24). "X potential duplicates detected." Email digest to admin. "Review Duplicates" link to admin page with side-by-side comparison. Admin can merge or dismiss.

**9.23 — Automated report generation** — Scheduled report generation (cron): "Generate monthly PRS report for [District] on the 1st of each month." "Generate statewide annual report on January 15." Stores generated PDFs. Emails to configured recipients. Configurable per report template.

**9.24 — Automated data quality checks** — Weekly cron: run all data quality checks (1.21). Generate data quality report. "X issues found." Email to admin. "View Data Quality Report" dashboard link. Tracks issue counts over time: "Data quality improving? [chart]."

#### Integration Automation (6)

**9.25 — PRS-to-DESE data auto-join** — When viewing PRS district data: auto-fetch the latest DESE metrics for that district. Cache the joined result. "PRS & DESE data auto-joined. Last synced: [datetime]."

**9.26 — PRS-to-document auto-linking** — When a PRS case is saved with a new filing_date: scan documents table for matching org_id + date proximity. "X documents may be related to this case. Review suggested links." Admin review page: suggested links with checkboxes. "Link Selected" or "Ignore."

**9.27 — PRS calendar feed** — iCal feed at `/prs/calendar.ics`: upcoming PRS deadlines as calendar events. Subscribe in Google Calendar, Outlook. "PRS Deadlines — Parent Data Force." Updates daily. Each event: case number, district, deadline type, link to case detail.

**9.28 — PRS RSS feed** — RSS feed at `/prs/rss`: new PRS cases, findings issued, cases closed. Each item: title, description, link to case detail. "Subscribe to PRS updates." Standard RSS format, compatible with feed readers.

**9.29 — PRS webhook notifications** — Admin-configurable webhooks: "POST to [URL] when new PRS case filed." Payload: JSON with case details. Secret token for verification. "Add Webhook" in admin panel. Rate-limited. Logs webhook delivery status.

**9.30 — PRS API changelog** — Page documenting all PRS API changes: new endpoints, deprecated endpoints, breaking changes. "API v1.2 — 2026-01-15: Added cross-reference endpoint." Versioned API: `/api/v1/prs/...`. Current version displayed in API responses. Backward compatibility: old endpoints continue working for 6 months after deprecation.

---

### Phase 10: PRS Production & Integration (30 subphases)

#### Performance & Caching (6)

**10.1 — PRS query optimization** — Add covering indexes: `prs_cases(org_id, filing_date)`, `prs_cases(current_status, filing_date)`, `prs_events(prs_case_id, event_date)`, `prs_findings(prs_case_id)`. Analyze slow queries via MariaDB slow query log. Target: all PRS pages load in < 500ms.

**10.2 — PRS data caching** — All PRS API responses cached with `fetchAllCached` at 300s TTL. Dashboard pre-computed aggregates cached at 600s. Case detail pages: cache the full case object (cases + events + findings + documents) for 120s — short TTL because case status can change.

**10.3 — PRS page caching** — Static pages (rankings, category explorer, data stories) use full-page caching: save rendered HTML to file, serve on subsequent requests, invalidate on data refresh. Admin bypasses cache. "Page cached at [datetime]." "Refresh Cache" admin button.

**10.4 — PRS lazy loading** — On case detail: load events and findings asynchronously after the main case data. Show skeleton while loading. "Loading timeline..." → timeline appears. "Loading findings..." → findings appear. Reduces initial page weight. IntersectionObserver for below-fold sections.

**10.5 — PRS image/chart caching** — Chart images (for embeds, social sharing) cached for 24h. Generated on first request, served from cache thereafter. "Chart image cached. Refreshes daily." Cache cleared on data refresh.

**10.6 — PRS CDN considerations** — All PRS static assets (JS, CSS) served with cache headers. No CDN needed for dynamic data. If CDN added later: exclude `/api/prs/*` and `/prs/*` from CDN caching (dynamic content). .htaccess rules updated.

#### Mobile & Responsive (6)

**10.7 — PRS mobile navigation** — PRS pages at < 768px: single-column layout. Charts stack vertically. Tables switch to card layout (each row becomes a card). Timeline becomes a vertical list with smaller dots. "Back to PRS Home" sticky header.

**10.8 — PRS mobile charts** — All PRS charts responsive: Chart.js `responsive: true` with aspect ratio adjustments. At < 480px: simplified charts (fewer data points, smaller legends, abbreviated labels). Touch-friendly tooltips (larger hit area, appear above touch point).

**10.9 — PRS mobile case detail** — Case detail at mobile: header collapsible sections (Allegations, Findings, Timeline, Documents). User expands one section at a time. "Show All" expands everything. Timeline vertically scrollable. Document links open in new tab.

**10.10 — PRS mobile filters** — Filter bars collapse to a "Filters" button that opens a bottom sheet. Apply filters → sheet closes → results update. Active filter count shown as badge on button: "Filters (3)." Clear all filters button.

**10.11 — PRS mobile map** — Map at mobile: simplified view (county-level only, not individual districts). Pinch-to-zoom disabled (use browser zoom). "View Full Map" link opens desktop-optimized version. Map controls below map (not overlaying).

**10.12 — PRS mobile performance** — Target: PRS pages load in < 2s on 4G. Reduce initial data payload: paginate lists at 10 items (vs 25 desktop). Lazy-load images. Defer non-critical JS. Test on real devices.

#### Accessibility (6)

**10.13 — PRS screen reader support** — All PRS pages: semantic HTML (headings, lists, tables with captions). Charts have sr-only text descriptions. Timeline events marked as list items with aria labels. Status badges: aria-label = "Status: Open." Color not the only indicator — text always present.

**10.14 — PRS keyboard navigation** — Tab order logical: skip-to-content link → filters → results → pagination. Data tables: arrow keys navigate cells. Charts: tab to chart, arrow keys cycle data points (5.9 pattern). Modals trap focus.

**10.15 — PRS color accessibility** — All PRS charts pass WCAG AA color contrast (≥3:1 for graphical elements, ≥4.5:1 for text). Color-blind safe palette available (13.3). Charts distinguishable in greyscale. Tested with simulation tools.

**10.16 — PRS text alternatives** — Every chart has a text alternative: either a data table below the chart or a long description in a collapsible section. "View as Table" link for all charts. Screen reader: "Chart: PRS Filing Trends. Data table below."

**10.17 — PRS focus indicators** — All interactive elements have visible focus rings: `outline: 2px solid var(--accent-glow); outline-offset: 2px;`. Custom focus styles consistent with dark theme. No `outline: none` without replacement.

**10.18 — PRS accessibility statement** — Page at `/accessibility`: documents accessibility features, known limitations, contact for issues. "We strive for WCAG 2.1 AA compliance. Report an accessibility issue: [email]." Linked from footer.

#### Security & Privacy (6)

**10.19 — PRS data access control** — PRS case detail: public can view summary, status, dates, categories. Admin-only: full event timeline, findings detail, documents marked internal, corrective action details. `is_public` flag on prs_events and prs_findings controls visibility. Public sees "This section is not publicly available" for restricted content.

**10.20 — PRS PII redaction** — Complainant names, student names, specific allegations involving individuals: never stored in public-facing fields. `prs_cases` has a `public_title` and `public_description` separate from admin-only fields. Admin sees full detail; public sees redacted version. Redaction enforced at the query level (different SELECT columns based on auth).

**10.21 — PRS document access control** — Documents linked to PRS cases respect document_workflow.is_public. Public can only see/download public documents. Admin sees all documents. "Request Access" button for restricted documents (sends email to admin).

**10.22 — PRS API rate limiting** — `/api/prs/*` endpoints: 100 requests per minute per IP. Returns 429 with Retry-After header if exceeded. Admin endpoints: 300/min. Configurable in system_config. Rate limit status in response headers.

**10.23 — PRS audit logging** — All PRS data changes logged to audit_log: case create/edit, event add/edit/delete, finding add/edit, document link/unlink. Logs: admin_user_id, action, entity_type='prs_case|prs_event|prs_finding', entity_id, old_values (JSON), new_values (JSON). Existing audit infrastructure reused.

**10.24 — PRS data backup** — PRS tables included in database backup procedures. Export all PRS data nightly as CSV (stored off-server). Backup verification: monthly restore test. "Last backup: [datetime]. Size: X MB."

#### Integration with Existing Platform (6)

**10.25 — PRS in main navigation** — "PRS Tracker" link in nav (between Cases and Districts). Dropdown: "Case List", "Analytics Dashboard", "District View", "Map". Active state highlighted.

**10.26 — PRS on homepage** — Homepage "What We Track" section: add "PRS Tracker" card. Homepage stats: include PRS case count. Homepage "Recent Activity": include recent PRS case events. Homepage data glance: mini PRS trend chart (optional — toggle in admin).

**10.27 — PRS on district detail** — District detail has a "PRS Activity" tab (5.13). Shows key PRS stats + mini charts + recent cases. No duplicate page — this tab is an embedded view of the PRS district page.

**10.28 — PRS on school detail** — School detail has PRS context section (5.17): shows parent district's PRS summary. "This school is in [District], which has X PRS cases." Link to district PRS view.

**10.29 — PRS in site search** — Including PRS cases in main site search. PRS results labeled "PRS Case" with distinct icon. Search results page shows mixed results (articles, cases, PRS cases, districts). Relevance scoring: PRS cases weighted equally with other case types.

**10.30 — PRS in sitemap** — PRS pages in sitemap.xml: `/prs`, `/prs/analytics`, `/prs/categories`, `/prs/map`, all individual case pages (changefreq: monthly, priority: 0.5). All public district PRS views included. Generated dynamically from active cases + districts.

## Critical files & anchors

| File | Symbol | Why |
|------|--------|-----|
| `dev/app/Controllers/PrsController.php` | New — entire class | Hub controller for all PRS routes: list, show, analytics, categories, districtView, map. All PRS pages route through here. |
| `dev/app/Models/PrsCase.php` | New — model class | PRS data access layer: queries, computed fields (deadlines, days_open), caching. Controllers call this, not raw SQL. |
| `dev/app/Components/Timeline.php` | New — component | Reusable vertical timeline component. Accepts events array, renders SVG/CSS timeline. Used by PRS case detail and district PRS view. |
| `dev/backend/schema.sql` | PRS tables section | New tables: `prs_cases`, `prs_events`, `prs_findings`, `prs_categories`, `prs_regulations`, `prs_finding_regulations`, `prs_case_links`, `chart_annotations`. All with FKs and indexes. |
| `dev/app/Controllers/ApiController.php` | `data()` method | All PRS chart API endpoints route through existing `type` switch. Add 12 new `case` branches for PRS analytics. |

## Verification

**Phase 1**: Visit `/prs` → paginated list of 8,550+ PRS cases with filters. Visit `/prs/PRS-2024-0016` → case detail with events, findings, documents.

**Phase 2**: Case detail shows timeline with colored dots, deadline countdown. Click document → opens viewer. Admin adds event → timeline updates.

**Phase 3**: Visit `/prs/analytics` → category doughnut, status bar, filing trend, resolution histogram, district volume bar, YoY change. All charts load data from API.

**Phase 4**: Visit `/prs/map` → SVG map colored by PRS filing rate. Year slider changes colors. Click district → slide-out panel.

**Phase 5**: Visit combined dashboard → PRS vs restraint scatter plot shows correlation coefficient. District cross-reference tab shows side-by-side metrics.

**Phase 6**: Upload document to PRS case → appears in linked documents panel and timeline. Full-text search across findings returns results.

**Phase 7**: Sankey diagram shows PRS workflow. Calendar heatmap shows filing patterns. Narrative generator produces district summary.

**Phase 8**: Rankings page: sortable tables, peer group comparisons, composite scoring. Rank change arrows. Top 10 / bottom 10 quick filters.

**Phase 9**: Download district PRS report as PDF. Export cases CSV. Dashboard shows deadline alerts. Weekly email digest sent.

**Phase 10**: All pages < 500ms load time. Mobile fully responsive. WCAG AA compliant. PRS in nav, search, sitemap. Admin audit log records all changes.

## Assumptions & contingencies

- PRS data (8,550 records in `prs_intakes`) is migrated to new `prs_cases` + `prs_events` + `prs_findings` schema. The migration script handles mapping: status→current_status, closure_code→resolution_type, category/subcategory→prs_findings rows, intake_date/findings_date→prs_events rows. If migration reveals data quality issues (missing dates, inconsistent statuses), affected records flagged for admin review rather than blocking migration.
- SVG district map from main visualization plan (Phase 11) is available. If not yet built, PRS map falls back to a county-level chloropleth using a static 14-county SVG (simpler, no district boundaries needed).
- D3.js and Sankey plugin are loaded from CDN for advanced visualizations (Sankey, network graph). If CDN unavailable, these charts degrade to simpler Chart.js alternatives (grouped bar for workflow stages instead of Sankey).
- Investigator/assignee data may not be available — all features referencing investigator assignment (7.6, workload charts) are marked as placeholders. Implement without them; add when data becomes available.
- Deadline emails require a configured mail server. If SMTP not configured, emails are logged to a file (`dev/backend/logs/prs_emails.log`) instead of sent. Admin can view the log.
- Chart.js component (`dev/app/Components/Chart.php`) and color scale system from main visualization plan must be implemented first (Phase 1-2 of main plan). PRS plan reuses these — verification of PRS charts depends on the Chart component existing.
