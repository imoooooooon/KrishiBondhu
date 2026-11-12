# Krishi Bondhu design system (v1.0)

> **Source of truth for every visual decision.** [IA.md](IA.md) says *what* is on each page and where it lives;
> this file says *how it looks*. Live reference: **`/design-system`** (run `composer dev`). Code: `public/css/tokens.css`,
> `public/css/app.css`, `resources/views/components/ui/`. Living document: change it in the same PR as the CSS.

**Lineage.** Adapted from the Al Wasil design system by Md. Mahfuz Ahmed Emon (two-tone surfaces, semantic tokens,
one-highlight rule, Bangla metrics, component anatomy), re-tuned for agriculture: leaf-green *Paddy* replaces Emerald,
*Sprout* replaces Lime, *Mustard* and *Soil* are added for harvest and earth, and type/controls are larger for outdoor
phone use. No CSS framework and no build step: plain CSS custom properties + Blade components.

## 1. Principles

1. **White first.** Most of every screen is a white canvas with grey elements (`--card`). Colour is spent on purpose.
2. **Paddy green carries trust.** Primary actions, links, brand surfaces, the farmer's money.
3. **Sprout is a highlighter.** One sprout highlight per region (a key figure, progress, the active state). Never as
   text or thin lines on white (contrast fails). On white it is only a filled shape carrying paddy text.
4. **Readable in the field.** Body text 16 px, controls 48 px, touch targets ≥ 44 px. Users are often outdoors, on
   budget phones, and some are new to websites (SRS F24).
5. **Bangla is first-class.** Every component wraps Bangla, never truncates it. Bangla gets larger metrics and no capitals.
6. **Real, never generated.** Real farm photos, or a calm tinted placeholder with an icon. No stock people, decorative
   gradients, glassmorphism, 3D icons or emoji.

## 2. Colour

### 2.1 Scales (raw: design-system page, illustrations and charts only)

| Scale | Key step | Role |
| --- | --- | --- |
| **Paddy** 50–950 | `paddy-800 #134A23` | Brand green (the 30 %) |
| **Sprout** 50–800 | `sprout-300 #CBE873` | Highlighter (the 10 %) |
| **Shoot** 50–300 | `shoot-200 #C9E6B3` | Soft tint: selected states, success |
| **Mustard** 50–800 | `mustard-300 #F7CC45` | Harvest and market moments (fills, chart series, tags) |
| **Soil** 50–800 | `soil-500 #8B6440` | Earth: cattle, soil guidance, illustrations |
| **Neutral** 0–900 | `neutral-900 #101A15` | Green-tinted neutrals, never pure grey |

### 2.2 Semantic roles (use these in every view)

| Token | Light | Use |
| --- | --- | --- |
| `--canvas` | white | Page background (60 %) |
| `--card` | `#F3F5F2` | Grey elements on the canvas: cards, list groups, icon buttons |
| `--surface` | white | Inputs, sheets, dialogs, secondary buttons |
| `--fg` / `--fg-muted` / `--fg-subtle` | 17.8 / 6.4 / 5.4 : 1 | Primary, secondary, tertiary text |
| `--accent` / `--on-accent` | paddy-800 / white | Primary action (sprout in dark) |
| `--accent-fg` | paddy-800 | Links, active icons |
| `--accent-bg` / `--accent-bg-strong` | shoot-100 / 200 | Selected rows, chips, active nav |
| `--highlight` / `--on-highlight` | sprout-300 / paddy-800 | The one highlight per region |
| `--brand` / `--brand-deep` | paddy-800 / 950 | Brand cards; footer and deep sections |
| `--harvest-bg` / `--on-harvest-bg` | mustard-100 / 800 | Harvest/price tags and cards |
| `--earth-bg` / `--on-earth-bg` | soil-100 / 700 | Cattle and soil tags and cards |
| `--success-*` `--info-*` `--warning-*` `--danger-*` `--neutral-*` | see tokens.css | Status (fg, bg, border) |

**Never hard-code a hex value in a view.** If a role is missing, add a semantic token to `tokens.css` (light and dark) and to this table.

### 2.3 60 / 30 / 10

| Share | Family | Where |
| --- | --- | --- |
| 60 % | White and neutrals | Canvas, cards, inputs, most text backgrounds |
| 30 % | Paddy + shoot tints | Primary buttons, brand cards, selected states, links, key icons |
| 10 % | Sprout | Active state, one key figure, progress on brand surfaces, "New" |

Mustard and soil are domain accents, not a fourth brand colour: tags, small cards and chart series only.
Review check: two competing sprout areas in a region → remove one. Paddy covering more than a third of a form → too heavy.

### 2.4 Contrast (WCAG 2.2)

Paddy-800 on white 10.3:1 · white on paddy-800 10.3:1 · paddy-800 on sprout-300 7.5:1 · paddy-800 on shoot-200 7.6:1 ·
mustard-800 on mustard-100 8.2:1 · soil-700 on soil-100 7.7:1 · `--fg-muted` on white 6.4:1. **Sprout on white fails (1.3:1).**

## 3. Typography

- **Families:** **Inter Tight** (Latin, SIL OFL, committed in `public/fonts/`) and **Li Ador Noirrit** (Bangla,
  as in Al Wasil), with **Noto Sans Bengali** (SIL OFL, committed) as the Bangla fallback. Bangla glyphs fall
  through to the Bangla family automatically.
- **Li Ador Noirrit (Lipighor):** committed in `public/fonts/li-ador-noirrit/` (Unicode Regular, SemiBold, Bold;
  the full download is kept in `resources/fonts/LiAdorNoirritMultiweight/`), used **with permission from Lipighor**
  obtained by Emon for this project. Keep the footer credit "ওয়েবফন্ট- lipighor.com" and do not rename or modify the
  font. Use only the Unicode files; the ANSI ones are
  for legacy Bijoy text and break on the web.
- **Scale** (`.t-*` classes; sizes switch automatically inside `lang="bn"`):

| Class | Latin | Bangla | Use |
| --- | --- | --- | --- |
| `t-display-xl` | 34–56 px / 1.08, −0.04em | 32–52 px / 1.3 | Website hero only |
| `t-display` | 32 / 40 | 33 / 48 | Section titles, key figures |
| `t-title-1` | 24 / 32 | 25 / 38 | Page title (the single h1) |
| `t-title-2` | 20 / 28 | 21 / 34 | Section headers |
| `t-title-3` | 17 / 24 | 18 / 28 | Card titles |
| `t-body` | 16 / 24 | 17 / 29 | Default |
| `t-callout` | 14 / 20 | 15 / 24 | Helper and secondary text |
| `t-caption` | 13 / 18 | 14 / 22 | Metadata (minimum size) |
| `t-overline` | 12 / 16 caps | 14 / 22, no caps | Section labels |

- **Numbers:** tabular figures (`.t-num`, `<x-ui.price>`). Taka shown as `৳` before the amount, lakh grouping
  (`৳ 1,25,000`), Bangla digits on Bangla pages. Losses carry a minus sign *and* danger colour.

## 4. Size, space, shape

- **Controls:** large 48 px (default), medium 40, small 32; md/sm extend their hit area to 44 px. Inputs are 48 px with 16 px+ text.
- **Spacing:** 4 px grid (`--space-1` … `--space-20`). Card padding 20 px. Gap between cards 16 px. Section rhythm `--section-space`.
- **Layout:** container 1180 px, narrow 720 px for forms; gutters 20 / 32 / 40 px; breakpoints 600, 850, 1050 (nav collapses), 1100 px.
- **Radius:** xs 6 badges · sm 10 small buttons · control 12 inputs/buttons · lg 16 list groups/media · card 24 · panel 28 sections · inset 8.
- **Tone over borders:** cards are told apart by tone, not strokes. Borders only for inputs, secondary buttons and the selected state.
- **Elevation:** shadows tinted with paddy (`--shadow-small/medium/modal`), never pure black.

## 5. Components

Use Blade components. Each file in `resources/views/components/ui/` documents its props at the top.

| Component | Purpose |
| --- | --- |
| `<x-layouts.app title narrow>` | Page shell: skip link, header, main, footer, theme/JS. Every page uses it. |
| `<x-ui.page-header>` | Breadcrumbs, the h1, a subtitle, actions |
| `<x-ui.section-heading>` | Eyebrow + display title + copy for website sections |
| `<x-ui.button>` | primary, accent, secondary, tertiary, danger · lg/md/sm · `href` makes a link · icons |
| `<x-ui.icon-button>` | Round icon-only control (label required), optional badge |
| `<x-ui.card>` | card / surface / brand / harvest / earth · optional `title` and `actions` slots · `href` |
| `<x-ui.media-card>` | Listings, products, markets, vets, jobs: image or placeholder, badge, meta, footer |
| `<x-ui.badge>` | Status (success, info, warning, danger, neutral, highlight, harvest, earth). Always text, optional icon |
| `<x-ui.chip>` | Filters (link or toggle) |
| `<x-ui.alert>` | Inline banners with icon and title |
| `<x-ui.field>`, `<x-ui.select>`, `<x-ui.textarea>`, `<x-ui.checkbox>` | Labelled inputs with hints and errors from `$errors`/`old()` |
| `<x-ui.stat>` | Figure + label + change (with sign and icon) |
| `<x-ui.list-row>` inside `.list-group` | Rows with icon, title, subtitle, trailing content |
| `<x-ui.empty-state>` | Icon, title, one line, one action |
| `<x-ui.price>` | Taka formatting (lakh grouping, Bangla digits) |
| `<x-ui.progress>` | Accessible progress bar |
| `<x-ui.icon>` | Lucide line icons (ISC), 1.75 px stroke; `cow-head` from Lucide Lab |
| `<x-ui.logo>` | Sprout mark + bilingual wordmark |
| `.table-wrap > .table` | Data tables; numbers right-aligned with `.num` |

Rules:
- One primary button per region. Destructive actions use `danger` and a confirmation step.
- Status is never colour alone: badge text, plus an icon where it helps.
- Pages put their content in `.container` (or use `narrow`). Use `.stack`, `.cluster`, `.grid`, `.grid-2` for layout before writing new CSS.
- New component? Add it to `components/ui/`, its CSS to `app.css` (component section), a sample to `/design-system`, and a row here.
- Feature-specific CSS or JS is allowed only when a component cannot express it. Keep it in `public/css/` or `public/js/` with the feature name, loaded on its pages only.

## 6. Icons and imagery

- Lucide-style line icons at 20 px (16 small, 24 large), round caps, `currentColor`. Decorative by default.
  Icon-only controls need a label.
- Domain icons: wheat (marketplace), trending-up (prices), store (supplies), cow-head (cattle), stethoscope (health), tractor (jobs).
- Photos: real crops, animals, fields and markets supplied by users. Crop listings use 16:10 media. No filters.
  Use `loading="lazy"` and the image size the layout needs.
- No photo? Use the media-card placeholder (shoot tint + icon), never a stock image.
- Illustrations, if added later: flat, few strokes, palette paddy, sprout, shoot, mustard, soil; no faces or minimal faces.

## 7. Motion

| Token | Duration | Use |
| --- | --- | --- |
| `--duration-fast` | 120 ms | Press, toggles |
| `--duration-base` | 200 ms | Hover, header state |
| `--duration-slow` | 280 ms | Sheets, menus |
| `--duration-reveal` | 650 ms, `--ease-emphasized` | Once-only section reveal (`data-reveal`) |

No looping animation, scroll-jacking or parallax. `prefers-reduced-motion` and low-data mode remove motion.

## 8. Dark theme

Follows the device by default. The header toggle stores the choice per browser (`localStorage: kb-theme`).
In dark, **sprout becomes the primary action** (paddy text on sprout), the canvas is `#0A110C`, cards are lifted
(`#141D17`), and the footer keeps its deep green. Every semantic token has a dark value in `tokens.css`. Check both
themes on `/design-system` before merging UI work.

## 9. Bangla, low data and accessibility

- Set `lang="bn"` on Bangla content (the layout sets `<html lang>` from the app locale). Bangla metrics apply automatically.
- Layouts must wrap. Never truncate Bangla names, money or status.
- **Low-data mode (F24):** add `data-low-data` on `<html>` to hide decorative images and remove motion. Fonts are
  subset WOFF2 (~45 KB Latin + ~105 KB Bangla) with `font-display: swap`. There are no CSS or JS frameworks.
- WCAG 2.2 AA minimum. One h1 per page. Visible focus (2 px `--focus` outline). Touch targets ≥ 44 px.
  Labels on every input. Errors are linked with `aria-describedby`. The skip link goes to `#main`.
- Forms work without JavaScript. JS only enhances (theme, menu, reveal).

## 10. Do and don't

| Do | Don't |
| --- | --- |
| White canvas, grey cards, one paddy anchor | Fill screens with green or sprout |
| Sprout once per region, as a filled shape | Sprout text or thin lines on white |
| Mustard/soil for harvest and earth tags | Use mustard as warning or as a button colour |
| Shoot tint for selected states | Solid paddy for large selected areas |
| Semantic tokens and `<x-ui.*>` components | Hex values, inline styles, a CSS framework |
| Real farm photos or the placeholder | Stock people, emoji, 3D icons, decorative gradients |
| Bangla wraps with taller lines | Truncate Bangla or use capitals in Bangla |

## Change log

| Date | Change | By |
| --- | --- | --- |
| 2026-11-12 | v1.1: Bangla font Li Ador Noirrit (committed with Lipighor permission, Noto fallback, footer credit); basic landing page. | Emon (with Claude Code) |
| 2026-11-12 | v1.0: tokens, type, components, design-system page; adapted from Al Wasil for agriculture. | Emon (with Claude Code) |
