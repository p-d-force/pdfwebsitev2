# CSS Refactor & Color System Plan

## Context

Survey of 17 CSS files (~6,300 lines), 49+ PHP files (~250+ inline styles), and 12 controllers revealed ~150+ places where hardcoded hex values are used instead of CSS variables. The color theme in `global.css` has 15 variables but is missing `--info` (#60a5fa), `--purple` (#a78bfa), and `--pink` (#ec4899). Chart.php, Timeline.php, and helpers.php each maintain their own palette arrays duplicating the same colors. Four map controllers duplicate the same percentile/HSL logic. Footer.php has ~25 inline styles. This plan: (1) centralizes all colors into a single `colors.css`, (2) extracts reusable inline styles to CSS classes, (3) deduplicates PHP color arrays, (4) shortens repetitive controller patterns.

## Approach

### Step 1: Create `colors.css` — single color authority

Create `dev/public/assets/css/colors.css` and move the `:root` block from `global.css` into it. Add the 3 missing variables: `--info: #60a5fa;`, `--purple: #a78bfa;`, `--pink: #ec4899;`. Add a `.chart-defaults` class and a `.map-colors` class with all the discrete/smooth/HLS palette hexes as CSS custom properties so Chart.php and map controllers can reference them by name rather than hardcoding.

Contents of `colors.css`:
```css
:root {
    --accent: #ff5a1f; --accent-hot: #ff3b1f; --accent-glow: #ffa366;
    --bg-primary: #0b0b0b; --bg-secondary: #161616; --bg-elevated: #1d1d1d;
    --bg-glass: rgba(255,255,255,0.04);
    --text-primary: #f5f5f5; --text-secondary: #a0a0a0; --text-muted: #767676;
    --border: #2a2a2a; --border-soft: #232323;
    --success: #22c55e; --warning: #f59e0b; --danger: #ef4444;
    --info: #60a5fa; --purple: #a78bfa; --pink: #ec4899;
    --radius-sm: 10px; --radius-md: 14px; --radius-lg: 20px;
    --shadow-soft: 0 8px 26px rgba(0,0,0,0.25); --shadow-strong: 0 20px 50px rgba(0,0,0,0.45);
    --glow: 0 0 36px rgba(255,90,31,0.2);
    --container: 1200px; --nav-height: 72px; --transition: 260ms ease;
    /* Palette swatches for map/compatibility */
    --palette-green: #22c55e; --palette-amber: #f59e0b; --palette-red: #ef4444;
}

/* Utility classes replacing common inline patterns */
.u-flex-center { display: flex; align-items: center; justify-content: center; }
.u-text-right { text-align: right; }
.u-text-muted { color: var(--text-muted); }
.u-mt-1 { margin-top: 1rem; } .u-mb-1 { margin-bottom: 1rem; }
.u-gap-1 { gap: 1rem; }

/* SR-only — screen reader utility */
.sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }
```

In `global.css`, replace the `:root` block with `@import url('colors.css');` and remove the `.sr-only` block (now in colors.css). Wire colors.css in `Layout.php` as the first stylesheet (before global.css) and in `admin/_layout.php`.

### Step 2: Deduplicate PHP color arrays into a single `ColorPalette` class

Create `dev/app/Core/ColorPalette.php` — a static class holding the canonical color arrays referenced by Chart.php, Timeline.php, helpers.php, and map controllers:

```php
class ColorPalette {
    public const DISCRETE = ['#22c55e','#f59e0b','#ef4444']; // green, amber, red
    public const ZERO = '#2a2a2a';
    public const CHART_PALETTES = [
        'default' => ['#ff5a1f','#ffa366','#22c55e','#f59e0b','#ef4444','#60a5fa','#a78bfa','#ec4899'],
        'sentiment' => ['#ef4444','#f59e0b','#22c55e'],
        'cool' => ['#60a5fa','#3b82f6','#2563eb','#1d4ed8','#1e40af'],
        'warm' => ['#ff5a1f','#f97316','#ea580c','#c2410c','#9a3412'],
    ];
    public const EVENT_COLORS = [ // Timeline + PRS status badges
        'filed' => '#60a5fa', 'accepted' => '#22c55e', 'investigating' => '#f59e0b',
        'findings_issued' => '#ef4444', 'closed' => '#767676', 'appealed' => '#a78bfa',
        'reopened' => '#ec4899', 'default' => '#767676',
    ];
    public const CHART_DEFAULTS = [
        'grid' => 'rgba(42,42,42,0.5)', 'tick' => '#767676', 'legend' => '#a0a0a0',
        'tooltipBg' => '#1d1d1d', 'tooltipBorder' => '#2a2a2a',
    ];

    /** HSL smooth color for a value in [min,max] range */
    public static function hslSmooth(float $value, float $min, float $max): string {
        $ratio = ($max - $min) > 0 ? ($value - $min) / ($max - $min) : 0;
        return 'hsl(' . round(120 * (1 - $ratio)) . ', 70%, 45%)';
    }
}
```

Apply this class:

- **Chart.php**: Replace `self::$palettes` (line 26) with `ColorPalette::CHART_PALETTES`. Replace hardcoded Chart.js defaults (lines 99-123, 214-238) with `ColorPalette::CHART_DEFAULTS`.
- **Timeline.php**: Replace `DOT_COLORS` const (line 15) with `ColorPalette::EVENT_COLORS`.
- **helpers.php**: Replace `status_badge()` and `finding_badge()` hardcoded arrays with `ColorPalette::EVENT_COLORS`. Change inline style output to use CSS classes `.badge-prs-{status}` instead of inline `style="background:...;color:..."`.
- **DataPortalController**, **PrsController**: Replace the 4 duplicate `$p33/$p66/$min/$max/HSL smooth` blocks (lines ~489-519, ~1846-1853) with calls to `ColorPalette::hslSmooth()`.

### Step 3: Extract Footer inline styles to `footer.css`

`dev/app/Components/Footer.php` has ~25 inline styles. Create `dev/public/assets/css/elements/footer.css` with classes for the footer grid, columns, links, headings, and copyright line. Replace all inline `style="..."` in Footer.php with class references. Wire footer.css in Layout.php alongside the other element sheets.

Footer structure to CSS-classify:
```css
.footer { background: var(--bg-secondary); border-top: 1px solid var(--border); padding: 3rem 0 2rem; }
.footer-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; max-width: var(--container); margin: 0 auto; padding: 0 1rem; }
.footer-col h4 { color: var(--text-primary); font-size: 0.9rem; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; }
.footer-col a { color: var(--text-secondary); font-size: 0.85rem; display: block; padding: 0.2rem 0; }
.footer-col a:hover { color: var(--accent); }
.footer-bottom { text-align: center; color: var(--text-muted); font-size: 0.75rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border); }
```

### Step 4: Replace inline resource-card borders with CSS modifiers

`dev/app/views/data-portal.php` has 6 resource cards with hardcoded `border-left: 3px solid #hex`. Create CSS modifier classes in `elements/cards.css`:
```css
.resource-card--restraint { border-left-color: var(--accent); }
.resource-card--discipline { border-left-color: var(--warning); }
.resource-card--enrollment { border-left-color: #06b6d4; }
.resource-card--attendance { border-left-color: #10b981; }
.resource-card--sped { border-left-color: #8b5cf6; }
.resource-card--prs { border-left-color: var(--pink); }
```
Apply the corresponding class to each card, remove the inline `style="border-left:..."`.

### Step 5: Replace inline county-detail styles with classes

`dev/app/views/counties/detail.php` uses inline `style="background:var(--color-surface,#1a1a2e);border:1px solid var(--color-border,#2a2a3e);..."` on stat cards. These duplicate `.stat-card` patterns already in `dashboard-styles` (or should be their own class). Create a `.county-stat-card` class in `districts.css` and replace all 5 inline blocks.

### Step 6: Replace inline map styles with utility classes

All 4 map views (`data/map.php`, `prs/map.php`, `data/town-map.php`, `prs/town-map.php`) share inline styles for:
- Tooltip container (`#map-tooltip`): `position:fixed;background:var(--bg-elevated);border:1px solid var(--border);border-radius:8px;font-size:0.8rem;pointer-events:none;z-index:1000`
- Legend: `display:flex;gap:0.5rem;justify-content:center;margin-top:1rem`
- Legend swatches: `padding:0.25rem 1rem;border-radius:4px;font-size:0.75rem;color:#fff`

Create these classes in `pages/data.css` and replace the inline blocks with `<div class="map-tooltip" id="map-tooltip">` etc. The JS will still control `display` and content but the styling moves to CSS.

### Step 7: Replace discipline notice inline style with a class

`DataSubController.php` line 308 has a massive inline style for the discipline data notice. Replace with `<div class="discipline-notice">` and define in `pages/data.css`:
```css
.discipline-notice { background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.25); border-radius: 6px; padding: 0.75rem 1rem; margin-bottom: 1.5rem; font-size: 0.85rem; color: var(--text-secondary); }
.discipline-notice strong { color: var(--warning); }
.discipline-notice a { color: var(--accent-glow); }
```

### Step 8: Shorten repetitive controller patterns

**DataSubController**: All 6 browser methods share identical `$page = max(1, (int)($_GET['page'] ?? 1)); $perPage = 50; $offset = ...` preamble. Move to a private method:
```php
private function paginateParams(): array {
    $page = max(1, (int)($_GET['page'] ?? 1));
    return [$page, DEFAULT_PER_PAGE, ($page - 1) * DEFAULT_PER_PAGE];
}
```
Replace the 3-line preamble in each of the 6 methods with `[$page, $perPage, $offset] = $this->paginateParams();`.

**Map color computation**: Extract the shared `p33/p66/min/max/range` block from DataPortalController (map + townMap) and PrsController (map + townMap) into a static helper in `ColorPalette`:
```php
public static function percentileColors(array $values): array {
    sort($values); $n = count($values);
    return ['p33' => $n>0 ? $values[(int)($n*0.33)] : 0, 'p66' => $n>0 ? $values[(int)($n*0.66)] : 0, 'min' => $values[0]??0, 'max' => $values[$n-1]??1, 'range' => ($values[$n-1]??1) - ($values[0]??0) ?: 1];
}
```
Call this once per method instead of inlining the sort/count/array-access logic.

**helpers.php cleanup**: Remove `excerpt()` (dead pass-through alias) and `generate_password_hash()` (unused). Tag them with `@deprecated` if removal feels aggressive.

## Critical files & anchors

| File | Region | Why |
|------|--------|-----|
| `dev/app/Core/ColorPalette.php` | New file | Single source of truth for all colors |
| `dev/public/assets/css/colors.css` | New file | All CSS variables + utility classes |
| `dev/app/Components/Chart.php` | `$palettes` (line 26), `render()` defaults (lines 88-125) | Replace with ColorPalette references |
| `dev/app/Components/Timeline.php` | `DOT_COLORS` (line 15) | Replace with ColorPalette::EVENT_COLORS |
| `dev/app/Components/Footer.php` | Entire file (~55 lines) | Replace ~25 inline styles with classes |
| `dev/app/Core/helpers.php` | `status_badge()` (line 233), `finding_badge()` (line 249) | Replace with ColorPalette + CSS classes |
| `dev/app/Controllers/DataSubController.php` | All 6 browser methods, line 308 notice | Paginate helper + CSS class |
| `dev/app/Controllers/DataPortalController.php` | `map()` lines 489-519, `townMap()` lines 556-582 | Replace with ColorPalette::percentileColors() |

## Verification

1. **Color audit**: Run `grep -r "#[0-9a-fA-F]\{6\}" dev/app/ dev/public/assets/css/ --include="*.php" --include="*.css" | wc -l` before and after — verify hardcoded hex count drops by 60%+.
2. **Visual regression**: Visit `/data/dashboard`, `/data/discipline`, `/prs/analytics`, `/data/map`, `/prs/map` — all charts, maps, badges render with correct colors.
3. **Footer**: Visit any page — footer renders with correct layout, all links functional.
4. **CSS validation**: Check browser console for no missing CSS file 404s.
5. **PHP syntax**: `php -l` on all changed files — zero errors.

## Assumptions & contingencies

- If `@import url('colors.css')` causes a flash of unstyled content, fall back to a `<link>` tag in Layout.php (same as other stylesheets).
- If removing `excerpt()` breaks any view, keep it but add `@deprecated` comment. The scout confirmed zero callers in the codebase.
- The `ColorPalette` class approach may conflict with Chart.php's existing `self::$palettes` if other code references it directly. Check for external callers of `Chart::$palettes` before replacing — if found, add a compatibility alias: `public static $palettes = ColorPalette::CHART_PALETTES;`.
