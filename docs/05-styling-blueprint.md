# 05 — Styling Blueprint

How CSS should be organized in the rebuild. No HTML pages — everything rendered through PHP. No single 21K-line monolith.

## CSS Architecture

```
public/assets/css/
├── global.css           # :root variables, reset, typography, layout shell
├── elements/
│   ├── buttons.css      # .btn, .btn-primary, .btn-secondary, .btn-ghost, .btn-tip
│   ├── cards.css        # .article-card, .case-card, .resource-card
│   ├── forms.css        # .form-input, .form-select, .form-textarea, .form-label
│   ├── tables.css       # .data-table, .filter-bar
│   ├── badges.css       # .status-badge (open, closed, pending, resolved)
│   └── nav.css          # .nav, .nav-menu, .nav-logo, mobile toggle
├── pages/
│   ├── home.css         # .hero, .hero-stats, .hero-cta, #featured, #quick-links, #data-glance, #updates-ticker, #cta-bottom
│   ├── articles.css     # .article-featured, .articles-grid, .article-card, .article-body
│   ├── cases.css        # .case-filters, .case-card, .case-meta-grid
│   ├── data.css         # .data-browser-tabs, .data-table, .filter-bar, .restraint-charts
│   ├── districts.css    # .district-hero, .district-dashboard
│   └── forms.css        # .submit-tabs, .submit-form
└── vendor/
    └── chart.css        # Chart.js overrides only
```

## Loading Strategy (PHP)

In `Layout.php`:
```php
<link rel="stylesheet" href="<?= asset('css/global.css') ?>">
<link rel="stylesheet" href="<?= asset('css/elements/buttons.css') ?>">
<!-- etc. -->
<link rel="stylesheet" href="<?= asset('css/pages/' . ($page_stylesheet ?? 'home') . '.css') ?>">
```

Each controller sets `$page_stylesheet` to determine which page CSS loads. Only needed CSS per page — no monolith.

## Brand Tokens (from :root)

Extract into `global.css`:

```css
:root {
  --bg-primary:    #0b0b0b;
  --bg-secondary:  #161616;
  --bg-elevated:   #1d1d1d;
  --accent:        #ff5a1f;
  --accent-hot:    #ff3b1f;
  --accent-glow:   #ffa366;
  --text-primary:  #f5f5f5;
  --text-secondary:#a0a0a0;
  --text-muted:    #767676;
  --border:        #2a2a2a;
  --success:       #22c55e;
  --warning:       #f59e0b;
  --danger:        #ef4444;
  --radius-sm:     10px;
  --radius-md:     14px;
  --radius-lg:     20px;
  --shadow-soft:   0 8px 26px rgba(0,0,0,0.25);
  --shadow-strong: 0 20px 50px rgba(0,0,0,0.45);
  --glow:          0 0 36px rgba(255,90,31,0.2);
  --container:     1200px;
  --nav-height:    72px;
  --transition:    260ms ease;
}
```

## Fonts

Google Fonts: `Inter` 300/400/500/600/700/800 + `JetBrains Mono` 400/500. Load in `<head>` via Layout.php.

## The Orange Glow (Button Signature)

```css
.btn-primary {
  background: linear-gradient(120deg, #ff3b1f, #ff5a1f);
  box-shadow: 0 6px 20px rgba(255,90,31,0.3);
  border-radius: 14px;
  color: #fff;
  border: none;
}
.btn:hover {
  transform: translateY(-2px);
}
```

This is THE brand signature. All primary CTAs use this pattern.

## Dark Theme Only

Brand is dark-theme-first. No light mode toggle. All backgrounds dark, all text light.

## Charts (Chart.js)

Loaded from CDN: `cdn.jsdelivr.net/npm/chart.js@4.4.0` — already in production CSP. Charts render via `<canvas>` with JS in page-specific `<script>` blocks at view bottom. Data comes from `/api/data` endpoints (JSON).

## Production Constraints

- Server: LiteSpeed (not Apache)
- CSP: jsdelivr.net, youtube.com, fonts.googleapis.com, fonts.gstatic.com approved
- No build tools: no SASS, no Webpack, no Node.js
- CSS must work as plain .css files served directly
- FTP-only deployment
