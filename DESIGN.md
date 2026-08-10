---
name: Keystone.guru
description: Dark, utilitarian war-room UI for planning and finding WoW Mythic+ dungeon routes
colors:
  dungeon-black: "#202020"
  deep-slate-navy: "#2B3E50"
  stone-surface: "#303030"
  stone-surface-raised: "#444444"
  keystone-green: "#00bc8c"
  keystone-green-deep: "#007053"
  steel-blue: "#375a7f"
  steel-blue-hover: "#4e7fb3"
  text-contrast: "#ebebeb"
  text-muted: "#adb5bd"
  raider-gold: "#ffbd0a"
  form-required-red: "#c40000"
typography:
  body:
    fontFamily: "Salesforce Sans, Arial, sans-serif"
    fontSize: "0.9375rem"
    lineHeight: 1.6
  headline:
    fontFamily: "Salesforce Sans, Arial, sans-serif"
    fontWeight: 500
  label:
    fontFamily: "Salesforce Sans, Arial, sans-serif"
    fontSize: "0.875rem"
rounded:
  sm: "0.25rem"
  poster: "6px"
  circle: "50%"
spacing:
  sm: "0.5rem"
  md: "1rem"
  section: "5rem"
components:
  button-primary:
    backgroundColor: "{colors.steel-blue}"
    textColor: "#ffffff"
    rounded: "{rounded.sm}"
  button-primary-hover:
    backgroundColor: "{colors.steel-blue-hover}"
  button-accent:
    backgroundColor: "{colors.keystone-green}"
    textColor: "#ffffff"
    rounded: "{rounded.sm}"
  button-accent-hover:
    backgroundColor: "{colors.keystone-green-deep}"
  card:
    backgroundColor: "{colors.stone-surface}"
    textColor: "{colors.text-contrast}"
    rounded: "{rounded.sm}"
  card-footer:
    backgroundColor: "{colors.stone-surface-raised}"
  input:
    backgroundColor: "{colors.stone-surface}"
    textColor: "{colors.text-contrast}"
    rounded: "{rounded.sm}"
---

# Design System: Keystone.guru

## Overview

**Creative North Star: "The War Room"**

Keystone.guru is a tactical planning table: a dark, focused room where a player walks in
with a dungeon and a key level, and walks out with a route they trust. Every surface serves
the plan for the next pull. The mood is **utilitarian and dense** — high information
density, no decoration for its own sake. Darkness is functional (it lets the dungeon maps
and data read as the bright layer), and the single green accent behaves like a marker on
the table: it points at what to click or what matters, never at itself.

The system is theme-based, not single-look: ~20 `--theme-*` CSS custom properties
(`resources/assets/sass/theme/_variables.scss`) drive three themes on the root element —
**darkly** (the flagship dark theme this document describes), **lux** (a light,
near-monochrome alternative), and **vapor** (a seasonal "Xal'atath" variant: WoW gold
`#d4af37` primary with void-purple `#6a2dbd` accent on the darkly structure). All component
work must be written against the theme variables so it holds in all three.

**Key Characteristics:**
- Dark tonal layering, no ambient shadows — depth is a background-color ladder.
- One working accent (Keystone Green in darkly) for links, CTAs, and "current/active".
- Bootstrap 5 + Bootswatch as the chassis; brand lives in the theme variables and density.
- Sturdy, legible controls: solid fills, visible borders, controls look like controls.
- The dungeon map and the data are the bright layer; chrome stays quiet around them.

## Colors

A near-neutral dark ladder with one hard-working green accent; color that isn't structural
is informational (gold for Raider.IO staff, red for required/danger).

### Primary
- **Steel Blue** (#375a7f): Bootstrap `primary` — default buttons, active states, brand-neutral emphasis. Hover: **Steel Blue Hover** (#4e7fb3).
- **Keystone Green** (#00bc8c): the accent that means "go" — links, accent CTAs (`--theme-btn-accent`), accent borders, the seasonal-affix highlight. Hover/deep: **Keystone Green Deep** (#007053).

### Neutral
- **Dungeon Black** (#202020): the page background (`--theme-darker`); the darkest rung.
- **Deep Slate Navy** (#2B3E50): the Bootstrap `$body-bg` darkly compiles against — a deliberate, permanent choice (not Bootswatch's near-black) that tints derived component colors (e.g. active nav-tabs); mostly invisible directly because the page forces Dungeon Black.
- **Stone Surface** (#303030): cards, headers, inputs, dropdowns (`--theme-dark`, `--theme-bg-card`).
- **Stone Surface Raised** (#444444): card footers; one rung lighter than its card.
- **Contrast Text** (#ebebeb): primary text on dark surfaces (`--theme-text-contrast`).
- **Muted Text** (#adb5bd): secondary text, disabled pagination, quiet labels (`--theme-light`).
- **Neutral Border** (rgba(128,128,128,0.5)): the standard control border — visible on both Stone Surface and Dungeon Black.

### Tertiary
- **Raider Gold** (#ffbd0a): reserved exclusively for Raider.IO staff attribution.
- **Required Red** (#c40000): required-field markers (`--theme-form-required`).

### Named Rules
**The One Accent Rule.** Darkly has one accent: Keystone Green. It marks links, accent
actions, and "current". Never introduce a second decorative accent; informational colors
(gold, red, WoW class/faction colors) are data, not decoration.

**The Theme Variable Rule.** Component CSS never hardcodes a darkly hex. Use
`var(--theme-*)` (or Bootstrap's `--bs-*` component vars) so darkly, lux, and vapor all
work from one rule set. A hardcoded `#303030` is a bug even when it looks right.

## Typography

**Body Font:** Arial (declared as "Salesforce Sans, Arial, sans-serif")
**Character:** Deliberately plain and instrumental — type is for reading data, not for
voice. Density comes from a slightly-under-default size with a generous line height.

> **Interim reality, recorded on purpose:** the `@font-face` for "Salesforce Sans" points
> to `/webfonts/Renogare-Regular.otf`, which is not shipped — everything renders in Arial.
> **Renogare is the intended brand font** (it is what Raider.IO uses and wants adopted),
> but adopting it requires real look-and-feel work (it runs large for this UI) that is
> deliberately deferred. Do not "fix" this piecemeal: adopting Renogare is a scoped
> redesign task, not a font-file swap. Until then, Arial is canon. The bundled
> `raleway.woff2` is unreferenced legacy.

### Hierarchy
- **Body** (400, 0.9375rem, 1.6): the global size — body, buttons, form controls, and dropdowns are all stepped down from Bootstrap's 1rem for density.
- **Headline** (Bootstrap h1–h6 defaults, 500): page and card titles; no custom display scale exists.
- **Label** (400, 0.875rem): Bootstrap's small/label sizing for metadata and table chrome.

### Named Rules
**The Data-First Type Rule.** No decorative type treatments. Emphasis is weight, contrast
color, or the accent — never a second typeface, letterspacing games, or all-caps headings.

## Layout

Bootstrap 5 grid with centered `.container` sections on content pages; the map pages are
full-bleed 100dvh applications where the map owns the viewport. Density is the point:
tables, card grids, and filter sidebars pack tightly at the 1rem spacer rhythm, with
`0.5rem` steps inside components.

- **Sticky shrinking header:** one sticky wrapper (`.ksg-header`, z-index 800) whose bars
  stack in normal flow; scrolling toggles a shrink class (context bar 99px → 64px, second
  navbar padding 1rem → 0.5rem halves, 0.2s ease). At rest the header band is transparent
  so page background art bleeds through the gutters; once shrunk it becomes an opaque
  Dungeon Black band (the gutters read as page, never as header). The map header instead
  floats permanently transparent with click-through gutters over the map.
- **Footer:** full-width Stone Surface band with 5rem vertical padding — the one
  deliberately spacious section in an otherwise dense system.
- **Ad slots are load-bearing:** anonymous users see ads (Patreon removes them); layouts
  must keep those slots viable. Never design a page that collapses without the ad column.
- **Breakpoints:** stock Bootstrap 5 (576/768/992/1200/1400); mobile is mobile web, and the
  footer and headers center/stack below 768px.

## Elevation & Depth

Flat by doctrine: depth is conveyed by **tonal layering**, not shadows. The ladder runs
Dungeon Black (#202020, page) → Stone Surface (#303030, surfaces on the page) → Stone
Surface Raised (#444, structure on a surface), with Deep Slate Navy as the compiled
Bootstrap body-bg tinting derived component states. Bootswatch's glow/shadow mixins are
present but deliberately gutted (empty bodies) — that is a choice, not an accident.

### Shadow Vocabulary
- **Poster lift** (`box-shadow: 0 2px 6px rgba(0,0,0,0.4)`; hover `0 6px 16px rgba(0,0,0,0.55)` + `translateY(-4px)`, 0.15s ease): route poster cards only — the one "featured object" treatment in the system.
- **Legibility scrim** (`text-shadow: 1px 1px 3px rgba(0,0,0,0.9)` over a black gradient scrim): white text over map-image backgrounds (poster cards, dungeon-name captions).

### Named Rules
**The Tonal Ladder Rule.** Surfaces at rest are flat. A shadow is earned only by floating
over imagery or the map (posters, overlays, tooltips) — never by an ordinary card, dropdown,
or input.

## Shapes

Quietly rounded rectangles: Bootstrap's 0.25rem radius on buttons, inputs, cards, and
badges; 6px on the poster cards (whose rounded corners clip their cover imagery); perfect
circles for anything that represents a person or an icon token (user avatars 26px,
select icons 24px, display icons 32px). Borders are 1px solid — the neutral
rgba(128,128,128,0.5) on controls, darker #222 on darkly form fields. No pills on buttons,
no cut corners, no ornamental geometry.

## Components

Component philosophy: **sturdy and legible** — solid fills, visible borders, no
translucency games; a control must look like a control at a glance in a dense screen.

### Buttons
- **Shape:** gently rounded (0.25rem), solid fill, 0.9375rem type.
- **Primary:** Steel Blue (#375a7f) fill, white text; hover lightens to #4e7fb3.
- **Accent:** Keystone Green (#00bc8c) fill for the page's "go" action; hover deepens to #007053.
- **Success/Info/Danger:** stock Bootswatch darkly variants; success doubles as the accent family.
- **States:** Bootstrap `--bs-btn-*` variable states; `.btn-check` toggle groups get their checked/hover styling from the unwrapped `.theme` rules (theme-wrapper compilation breaks the stock sibling selectors — keep using those rules).

### Cards / Containers
- **Corner Style:** 0.25rem; poster cards 6px.
- **Background:** Stone Surface body, Stone Surface Raised footer.
- **Shadow Strategy:** none at rest (Tonal Ladder Rule).
- **Border:** none on standard dark cards; tone difference does the separating.
- **Internal Padding:** Bootstrap card defaults (1rem).

### Signature Component: Route Poster Card
The system's one hero object (`.card_dungeonroute.poster`): a fixed-height (16rem) card
whose dungeon-map thumbnail is the full-bleed background, demoted behind a four-stop black
gradient scrim (0.35 → 0 → 0.5 → 0.85 top-to-bottom); white scrimmed metadata (title,
enemy forces) sits in the foreground. Carries the only lift shadow in the system and a
-4px hover raise. New "featured content" surfaces should reuse this pattern rather than
invent a second hero treatment.

### Inputs / Fields
- **Style:** Stone Surface fill, Contrast Text, 1px border (#222 in darkly; neutral gray on Tom Select), 0.25rem radius, 0.9375rem type.
- **Selects:** Tom Select with the Bootstrap 5 theme; the `.ts-wrapper.form-control` is the visible box (Stone Surface, neutral gray border); single selects draw a `currentColor` CSS caret; multi selects collapse to an "N selected" count summary instead of tag-bloating.
- **Required:** marked with Required Red (#c40000).
- **Disabled:** flat gray wash (rgba(128,128,128,0.2)).

### Navigation
- **Style:** dark navbars on `--theme-header` (Stone Surface); links in Muted Text, hover/active step up to Contrast Text with no underline (underline is reserved for body links on hover).
- **Sticky shrink behavior:** see Layout — transitions are 0.2s ease and animate height/padding/background only.
- **Game-version switcher:** quiet text links, active = Contrast Text, 0.25rem chip radius.

### Tables (DataTables)
- **Style:** striped rows via Dungeon Black odd-rows on Stone Surface, Muted Text chrome, dense 0.9375rem type; pagination in theme colors with Muted Text disabled states.

## Do's and Don'ts

### Do:
- **Do** write every component style against `var(--theme-*)` / `--bs-*` variables and sanity-check darkly, lux, and vapor (The Theme Variable Rule).
- **Do** keep density: 0.9375rem controls, 1rem rhythm, tight card grids — whitespace is spent only where it aids scanning.
- **Do** use Keystone Green (accent variables) for the one action or state that matters on a screen.
- **Do** reuse the poster-card pattern (cover image + gradient scrim + scrimmed white text + lift shadow) for any featured/hero content.
- **Do** keep ad-slot placements viable on anonymous-user layouts.

### Don't:
- **Don't** hardcode darkly hexes in component CSS — it silently breaks lux and vapor.
- **Don't** add shadows to resting surfaces (cards, dropdowns, inputs); depth is the tonal ladder.
- **Don't** introduce a second accent color or decorative palette; non-structural color must carry information (class colors, factions, staff gold, required red).
- **Don't** adopt Renogare (or any new typeface) piecemeal — it is a deliberate deferred decision owned by a future look-and-feel task.
- **Don't** use Raider Gold (#ffbd0a) for anything except Raider.IO staff attribution.
