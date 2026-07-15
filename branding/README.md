# Parent Data Force — Brand Reference

Self-contained brand guide. No decisions — just facts.

## Colors

```
--bg-primary:    #0b0b0b          near-black site background
--bg-secondary:  #161616          dark section backgrounds
--bg-elevated:   #1d1d1d          card/elevated surfaces
--bg-glass:      rgba(255,255,255,0.04)
--accent:        #ff5a1f          PRIMARY BRAND ORANGE
--accent-hot:    #ff3b1f          hot orange (gradient source)
--accent-ember:  #ff5a1f          ember orange
--accent-glow:   #ffa366          light orange (hover states, highlights)
--text-primary:  #f5f5f5          body text
--text-secondary:#a0a0a0          secondary text
--text-muted:    #767676          muted/disabled text
--border:        #2a2a2a          borders
--border-soft:   #232323          soft borders
--success:       #22c55e          green
--warning:       #f59e0b          yellow
--danger:        #ef4444          red
```

## Design Tokens

```
--radius-sm:     10px
--radius-md:     14px
--radius-lg:     20px
--shadow-soft:   0 8px 26px rgba(0,0,0,0.25)
--shadow-strong: 0 20px 50px rgba(0,0,0,0.45)
--glow:          0 0 36px rgba(255,90,31,0.2)
--container:     1200px
--nav-height:    72px
--transition:    260ms ease
```

## Fonts

- Body: **Inter** (weights 300, 400, 500, 600, 700, 800)
- Data/Code: **JetBrains Mono** (weights 400, 500)
- Source: Google Fonts
- URL: `https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap`

## Buttons — The Orange Glow

```css
.btn-primary {
  background: linear-gradient(120deg, #ff3b1f, #ff5a1f);
  box-shadow: 0 6px 20px rgba(255,90,31,0.3);
  border-radius: 14px;
  color: #fff;
  border: none;
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  cursor: pointer;
  transition: transform 260ms ease, box-shadow 260ms ease;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 28px rgba(255,90,31,0.45);
}

.btn-secondary {
  background: rgba(255,90,31,0.14);
  border: 1px solid rgba(255,90,31,0.4);
  border-radius: 14px;
  color: var(--accent-glow);
}

.btn-ghost {
  background: transparent;
  border: 1px solid var(--border);
  border-radius: 14px;
  color: var(--text-secondary);
}

.btn-tip {
  background: linear-gradient(120deg, rgba(255,90,31,0.2), rgba(255,90,31,0.1));
  border: 1px solid rgba(255,90,31,0.3);
  border-radius: 14px;
}
```

All buttons carry SVG `.btn-icon` at 16x16px inline.

## Logo

- File: `logo.png` (1024x1024 PNG)
- Displayed in nav (`.nav-logo-img`) and footer (`.footer-logo-img`)
- Nav text next to logo: "PARENT DATA FORCE" (`.nav-logo-text`)
- Favicon: same logo.png

## Site Identity

- Name: **Parent Data Force**
- Tagline: **MAKING DATA MAKE SENSE**
- Domain: `www.parentdataforce.com`
- Email: `admin@parentdataforce.com`
