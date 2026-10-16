# EthioSIS — UI/UX design guidelines & upgrade roadmap

This document records **(1)** how layout, proportion and interaction were designed for the EthioSIS
theme, **(2)** the principles behind them, and **(3)** the enhancements proposed next. It is written for
whoever keeps extending the theme, so the reasoning survives the next change.

The single source of truth for the modern UI layer is
`assets/themes/EthioSIS/css/theme.css`. `stylesheet.css` and `stylesheet_wkhtmltopdf.css` are **generated**
concatenations — edit files in `css/`, then run:

```sh
node assets/themes/EthioSIS/build.mjs
```

---

## 1. Why proportion and rhythm matter here

RosarioSIS is a data-dense admin app: long lists, wide forms, dense tables, repeated page shells. In that
setting the layout *is* the usability. Three effects dominate:

- **Scan cost.** Every extra column rule, inconsistent left edge or misaligned control adds a saccade the user
  must make on *every* row. Consistency (one spacing scale, one grid) is the single biggest win.
- **Hierarchy.** Users must tell, in under a second, *where they are* (page title), *what this screen is for*
  (sub-header), and *what they can do* (actions). If title, subtitle and button share the same weight, nothing
  reads as primary.
- **Line length & density.** Full-width prose on a 1920px monitor produces 150–200-character lines; the eye
  loses its place. Data tables, by contrast, *want* the width. Text and tables therefore need **different
  measures**, not one global width.

### Principles applied

| Principle | Source | How the theme uses it |
| --- | --- | --- |
| 8-point spacing grid | USWDS spacing tokens, Material | `--sp-1…--sp-6` (4/8/12/16/24/32). Nothing steps outside it. |
| Modular type scale | USWDS type tokens, Bringhurst | `--fs-1…--fs-6` (13→24px). Heading weight, not size, carries hierarchy. |
| Line length 45–75ch | Bringhurst / Butterick | Text blocks capped by container widths (see roadmap §4.1). |
| Proximity & similarity | Gestalt | Related fields share a card/fieldset; rows use consistent cell padding. |
| Hick's law | HCI | One primary action per view; secondary actions are quiet `.button`, primary is `.button-primary`. |
| Fitts's law | HCI | Interactive targets ≥ 24px (WCAG 2.2) — inputs 38px, rows 40px, submenu links 36px. |
| Recognition over recall | NN/g heuristic #6 | Labels stay visible; no placeholder-as-label. |
| Error prevention & recovery | NN/g heuristic #5, #9 | `.updated` / `.error` / `.warning` / `.note` message system; inline help. |
| Consistency & standards | NN/g heuristic #4 | One shell: sidebar + top bar + page header + content card. |
| Aesthetic & minimalist design | NN/g heuristic #8 | Legacy uppercase/borders stripped; white space replaces rules. |
| Contrast & target size | WCAG 2.1/2.2 | Text ≥ 4.5:1, large ≥ 3:1, targets ≥ 24px, visible focus ring. |

---

## 2. The layout system as it stands

- **Metrics tokens:** `--sidebar-w` 240px, `--topbar-h` 54px, `--content-max` 1400px (reading/form cap).
- **Motion tokens:** `--ease` (one curve) with `--dur-1` `.12s` / `--dur-2` `.22s`, all gated by
  `prefers-reduced-motion`.
- **Shared rhythm (section 23):** `--page-gap` 24px between top-level blocks, `--card-gap` 16px inside a card,
  `--field-gap` 12px between related controls. Two blocks are *always* exactly `--page-gap` apart.
- **Surfaces:** `--bg` (page) → `--surface` (card) → `--surface-2` (strips/rails) → `--surface-3` (hover).
- **Semantics:** `--brand` evergreen = actions/links/active; `--accent` brass = small highlights only;
  `--danger/--warning/--success/--info` for status. Colour is **reserved for meaning**, not decoration.
- **Responsive:** breakpoints at 874px (sidebar), 900px and 600px (form/search two-column → one-column).
- **Dark scheme:** flips every token and overrides the legacy rules that hard-code light colours. The scheme is
  stamped on `<html data-theme>` server-side from the `ethio_scheme` cookie, so first paint is already correct.

---

## 3. What was fixed in this pass

1. **Dark-mode date picker.** `calendar-blue.css` hard-codes a light grid (month title, day-name row, buttons,
   weekend/today/selected cells, month/year combo, time row). Only `tbody td` had been overridden, so the rest
   stayed white-on-dark — the reported "the whole calendar breaks". Every part is now re-skinned.
2. **jscalendar nav cells.** jscalendar puts `class="button"` on its navigation `<td>`s; the generic button
   component forced them to `display:inline-flex` 38px pills, knocking the picker's header row out of the table.
   They are reset to real table cells for both schemes.
3. **Colourbox event popup.** Legacy `#cboxLoadedContent { background:#fff !important }` beat the dark override
   (white panel, dark text at ~1.2:1). The dark rule now also carries `!important`.
4. **Attendance code chips.** The `-alt` variants paired white text with near-white fills (~1.1:1 in *both*
   schemes). Rebuilt from `--success/--danger/--warning` tokens; every code now ≥ 6:1.
5. **Calendar legend swatches.** Legacy `#afa/#aaf/#faa` now use the same `--day-*` tokens as the day washes.
6. **Sidebar interaction.** The open submenu hovered to a hard-coded `#fff` (invisible text in dark); pressed
   (`:active`) states were missing; the selected module row gave no hover feedback; the module icon tile vanished
   against the row hover. All tokenised and fixed.
7. **Dark-mode module icons.** The SVG glyphs are stroked in the legacy charcoal and vanished on dark tiles; they
   are lifted to white (active row keeps a dark glyph on its mint pill).
8. **Assorted legacy light patches** tokenised: `#courses_div`, `#student-comments`, `.bgcolor`, `.header2`,
   markdown-editor tabs, `.attendance-code` inputs.
9. **`--radius-lg`** was referenced but never defined; defined in the token block.
10. **Date-picker dimmed days.** The base sheet painted *other-month* days `#bbb` and weekends `#a66` on the
    `#eeefff` cell (~1.7:1 / 3.8:1, i.e. unreadable). They now follow `--text-muted` / `--danger`, ≥ 4.5:1 in
    both schemes, while keeping the "dimmed vs normal day" hierarchy.

All of the above — plus the five approved roadmap items below — are asserted by the automated audit in
`preview-audit.html` (contrast for 30 elements in light **and** dark, plus the sticky-header behaviour).

---

## 4. Upgrade roadmap — status

Ordered by impact ÷ effort. Each is additive and reversible. Of the eight proposals, **five were approved and
implemented** (4.1, 4.2, 4.3, 4.6, 4.8); three were **deferred** for a later pass (4.4, 4.5, 4.7).

### 4.1 Content measure & page width — ✅ implemented (high impact)
- Text blocks are capped at a readable measure: `#body p { max-width: 78ch }`, and form/prose surfaces at
  `--content-max: 1400px` (`.postbox-wrapper { max-width: min(100%, var(--content-max)) }`).
- Data tables deliberately keep the **full workspace width** — text and tables no longer share one measure.

### 4.2 One page-header pattern — ✅ implemented (high impact)
- `table.header` rows are vertically centred, `.header1` carries a tight `--sp-1` baseline pad, and the action
  cluster (`.align-right`) is right-aligned, `nowrap`, with consistent `--sp-2` gaps and middle alignment.
- A 600px override lets the title and actions wrap instead of overflowing on phones.

### 4.3 List ergonomics — ✅ implemented (high impact)
- `.list-wrapper` is the **scroll container** (`max-height: min(78vh, 860px)`) so the `overflow:auto` wrapper
  makes **sticky `<thead>`** actually pin on long lists; short lists never reach the cap, so no scrollbar shows.
- Numeric columns line up via `font-variant-numeric: tabular-nums`; print resets the cap (`.list-wrapper{max-height:none}`).
- Lists that contain a pop-up (colour picker, notes, polls) keep `overflow: visible` so nothing is clipped.
- Still open (needs page-by-page markup review): pinning row actions to the last column; generalising
  `.ethio-list-empty` to every list.

### 4.4 Form layout & widths — ⏸ deferred (medium-high impact)
- Enforce a **label : field** column ratio at ≥ 900px (partly done for `fixed-col`), group fields with
  `<fieldset><legend>`, and size text inputs to their expected content (a "Grade" input need not be 900px).

### 4.5 Accessibility hardening — ⏸ deferred (medium-high impact)
- **Skip-to-content** link; explicit `<nav>`/`<main>` landmarks; `aria-current="page"` on the active menu item;
  `aria-live="polite"` for the `.updated`/`.error` messages; focus trap + `Esc` in the colourbox.
- Keyboard support for the sidebar accordion (arrow keys, `aria-expanded` on the module row).
- *(The `prefers-reduced-motion` half of this was pulled forward and shipped with 4.8.)*

### 4.6 Dark-mode completeness + a contrast guard — ✅ implemented (medium impact)
- Finished the last legacy literals: `code`, `pre`, `hr` and `blockquote` are tokenised for dark mode; the
  date-picker's dimmed-day literals were re-pointed (see §3.10).
- The `preview-audit.html` harness is now a **guard**: on load it measures WCAG contrast for every critical
  element in *both* schemes and renders PASS/FAIL, logging failures to the console, plus it fills a long list to
  confirm the sticky header — so a regression is caught automatically, not by eye.

### 4.7 Icon system → CSS masks — ⏸ deferred (medium impact, long-term)
- The current icons are `background-image` SVGs with a *fixed* stroke colour, so dark mode needs `filter` hacks.
  Converting to **`mask-image` + `background-color: var(--icon)`** makes icon colour follow the theme cleanly and
  removes the filter trick. (Mechanical change across ~22 SVGs + `icons-svg.css`.)

### 4.8 Micro-interactions & feedback — ✅ implemented (medium impact)
- One easing curve and two durations (`--ease`, `--dur-1`, `--dur-2`); server-rendered feedback
  (`.updated/.error/.warning/.note`) gets a small `ethio-msg-in` entrance so a save is noticed.
- Every transition and animation is neutralised by a single `@media (prefers-reduced-motion: reduce)` guard at
  the end of the file.

---

## 5. How to verify a change

1. `node assets/themes/EthioSIS/build.mjs` (rebuilds `stylesheet.css` + `stylesheet_wkhtmltopdf.css`).
2. Open `preview-audit.html` (repo root) and read the **Automated audit** box at the top. It runs on load and
   re-runs on every scheme toggle, and reports one PASS/FAIL row per element for light **and** dark, plus the
   sticky-header result. Console failures are logged with `[EthioSIS audit]`.
3. The harness also fills a long list so the sticky header can be verified (it emulates the ≥ 1024px desktop
   layout; below that the legacy `zresponsive.css` flips `.rt` tables into a horizontal layout).

> Note: CSS `transition`s do **not** advance while the tab is hidden, so computed styles read immediately after a
> scheme swap can show the *old* colour. Disable transitions (`*{transition:none!important}`) when measuring.


---

## 6. Grayscale dark mode, Phosphor icons and layout rhythm (current pass)

### 6.1 Dark mode is now neutral grayscale
The dark scheme dropped its evergreen cast. One dark gray is the ground for the whole product and the text
hierarchy is the lightest gray that is *not* pure white, then progressively darker grays beneath it. Light mode
keeps the same idea inverted (dark grays on white).

| token | was | now |
|---|---|---|
| `--bg` | `#101413` | `#1a1a1a` (the main background) |
| `--surface` | `#191f1c` | `#212121` |
| `--surface-2` | `#212825` | `#292929` |
| `--surface-3` | `#2b332e` | `#333333` |
| `--border` / `--border-strong` | `#303933` / `#47534c` | `#353535` / `#757575` |
| `--text` | `#e9edeb` | `#f4f4f5` |
| `--text-soft` | `#c3ccc7` | `#d9d9d9` |
| `--text-muted` | `#9aa59f` | `#a3a3a3` |
| `--text-faint` | `#7b867f` | `#8a8a8a` |
| `--intro-wash` / `--intro-border` | evergreen | grayscale |
| `--day-noschool` | `#212825` | `#292929` |

**Green stays for active states only**: the selected-module pill, links, primary buttons, the active submenu
tint, and the semantic calendar washes (`--day-full`, `--day-minutes`) — three grays there would be
indistinguishable, so those keep their hue by design. Every text/background pair measures **WCAG AA** (the
weakest is `--text-faint` at 5.04:1); `--border-strong` was lifted to `#757575` so form controls still clear
3:1 and stay identifiable on a dark ground.

### 6.2 The identity card above the sidebar
Rebuilt as a proper card instead of a cramped strip. It is now a three-area grid —
`[avatar] Name ›` / `Role` (as a **badge**, so it can never be mistaken for part of the name) / `— today's date —`
spanning the full width under a hairline. Also removed dead DOM: the hidden duplicate username/date inside the
form, and the hidden sidebar logo link (the logo lives in the top bar); their now-orphaned CSS rules went too.
Verified in both schemes: nothing clipped, no horizontal overflow.

### 6.3 Phosphor Icons across the whole project
All **65** SVG files under `modules/` (21) and `btn/` (44) were swapped for Phosphor artwork — **filenames and
class names unchanged**, so no PHP or selector edits were needed. The 7 inline SVGs in `Side.php` and
`Bottom.php` (chevron, log-out, help, hamburger, back, sun, moon, print) were converted too, and the two rules
that *stroked* icons were flipped to `fill: currentColor` because Phosphor draws filled paths, not outlines.
Weight is **bold**: the legacy icons were 2px strokes at 24px (~1.7px rendered at the 20px module size) and
Phosphor regular draws at ~0.8px there, which reads as faint; bold lands at ~1.25px and matches the old weight.
The hamburger needed an override at *ID* specificity because three legacy rules still stroke it.
`tools/swap-icons.mjs` reproduces the swap (`--dry-run` to preview); originals are in `tools/icon-backup/`.

### 6.4 One spacing rhythm, page by page
Measured every module's main page on the running app. The dashboard alone had a **16 / 16 / 39 / 0** gap
rhythm: `table.header` used `--sp-4` while everything else used its own margins, and RosarioSIS drops bare
`<br>` spacer elements between blocks that render as ~20px line boxes no margin accounted for.

- `#body > *` now owns the rhythm at `--page-gap` (24px), outranking the legacy per-element margins.
- `#body > br { display: none }` removes the stray line boxes.
- `#body > .st` — the marking-period level lists (Year / Semester / …) were floated and content-sized, so they
  rendered as two ragged 142px / 181px columns under an otherwise full-width page. They now run down the page
  at full width, like every other list in the app.

**Result: uniform 24px gaps, aligned left edges and no horizontal overflow on all ~12 module main pages**
(Dashboard, Marking Periods, School Information, Configuration, Students, Users, Student Schedule, Courses,
GPA/Rank, Report Cards, Attendance Administration).

### 6.5 Forms on narrow screens
Below 874px RosarioSIS floats every `tr.st` cell to full width, which turned the Marking Periods edit form into
an eight-row ungrouped single-column list of label+input pairs. Every form now uses the same **2-up grid** the
fieldset forms already had: two related fields share a line, a lone field spans the row, controls follow their
label, and checkbox/radio labels keep the control on their own line. Desktop (≥875px) is untouched — the form
still lays out as a normal table row there.

### 6.6 Verifying
`browser.screenshot` needs the tab to be visibly focused, so verify in this order: `browser.tabs.focus`, then
`screenshot`. Transitions freeze in a hidden tab, so inject `*{transition:none!important}` before measuring
computed styles and remove it afterwards. Navigations reuse the browser's page cache, so append a `&nc=<random>`
query when a rebuilt stylesheet has to be picked up — the cache key is `filemtime` of `stylesheet.css` and a
plain navigate can serve the previous build.

## 7. Page-by-page layout audit (current pass)

A measured sweep of **all 69 module entry points** reachable from the sidebar (6 modules plus the dashboard):
top-level block gaps, left-edge alignment, horizontal overflow inside every container, label/control alignment,
button spill past the parent box, and clipped cell text. Not eyeballed — each page is loaded, waited on, and
probed via `getBoundingClientRect`.

Result: **every page holds a 24px top-level rhythm, a single left edge at x=12, and no horizontal overflow.**
The audit found the four defects below, none of which were visible by inspection.

### 7.1 Legacy specificity defeats the theme
The one recurring root cause. `theme.css` is concatenated last so it wins source-order ties, but several legacy
rules use a *type-qualified* selector — `div.error, div.updated` is (0,1,1) — which outranks the theme's bare
`.error` (0,1,0) **whatever order the files arrive in**. Loading last does not help.

Three properties on every save/error message were being won by legacy: padding stayed `3px 7px`, the left rule
stayed `#b80a04` / `#0baa38` instead of `--danger` / `--success`, and the light-mode danger wash stayed white.
The theme's message rules now restate themselves at legacy's own specificity by listing `div.error` and
`div.updated` alongside the bare classes. Non-`<div>` messages still fall through to the bare class, where
legacy never matched them anyway, and the dark override at (0,2,0) still beats all of it. Measured: `12px 16px`
padding, `--danger` left rule, 6.45:1 on the dark `--surface-2` box and 6.24:1 on the light `#fff0ee` wash.

Worth carrying forward: a contrast guard must compute ratios from **computed** styles, never from what the
stylesheet intends to declare.

### 7.2 Buttons were `content-box`
Legacy pins `input[type=submit|reset|button]` to `box-sizing: content-box` at (0,1,1), outranking the global
`*` reset. `width: 100%` therefore meant "100% of the column plus this rule's own 32px of padding", so every
full-width button bled past its container — measured 510px of button in a 476px toolbar slot on Portal Notes
and Polls, with the same spill on the login button. Those three input types now use `border-box`, and the two
rules that set `width: 100%` zero their horizontal margin. Buttons with `width: auto` size to content under
either model, so nothing else moved.

### 7.3 Date trios
RosarioSIS wraps month + day + year + calendar button in a `white-space: nowrap` span so the three stay
together. Three rules conspired to inflate it: `max-width: 100%` at two different specificities, plus
`width: 100%` outright inside `td.popTable form`. Capped at the cell width, each select took the whole cell —
164px each, a 547px trio in the 502px it was given (Attendance > Fix Daily Attendance). Nothing inside a
nowrap span can wrap, so those controls now size to their options instead.

Related: the 2-up grid added in 6.5 gives a row containing `.nobr` the full width rather than half, because a
293px nowrap trio cannot share a 259px column (Attendance > Percent).

### 7.4 Tooltips
Legacy hard-codes the bubble to `#111` on a `#000` edge with `#fff` text. That is a proper dark chip on the
light scheme, but in dark mode `#111` sits one step off the `#1a1a1a` page and the bubble loses its silhouette
exactly when it needs one. It now paints from `--text` / `--text-invert`: a near-white chip with `#1a1a1a` text
in dark (15.8:1), a near-black chip with white text in light (18.1:1).

### 7.5 Known variance
Pages that show a server message (`div.error` / `div.updated`) between the page header and the content measure
20px above the box and 28px below, instead of 24/24 — 4px of glyph overhang on the header line box. Below the
threshold of perception, and no page overflows because of it.

## 8. User review round: lighter dark ground, plain pages, real icon geometry

Five requests, all now live. Two of them were bugs rather than taste, and both were the same class of defect as
7.1: a rule the theme wrote at (0,1,0) losing to something that shares that specificity and is concatenated
later.

### 8.1 A lighter gray ground for the whole app
`#1a1a1a` read as near-black. The dark ground is now **`#2b2b2b`**, with the planes re-stepped so they still
separate: `--surface #343434`, `--surface-2 #3d3d3d`, `--surface-3 #484848`, `--border #4f4f4f`.

Lightening the ground costs roughly a point of contrast per step, so two text tokens moved up with it —
`--text-muted` `#a3a3a3 → #b0b0b0`, `--text-faint` `#8a8a8a → #949494` — and **the dark brand ramp was
re-based** (`--brand` `#3fb489 → #4dbf95`, `--brand-600` `#56c9a0 → #63cfa6`). That last one is not cosmetic:
`#3fb489` on the new `#3d3d3d` submenu panel measured **4.19:1**, below AA, where it had been 5.6:1 on the old
`#292929`. `#4dbf95` clears AA on every surface step it can land on (4.76 / 5.46 / 6.20).

### 8.2 Green off dropdown and button/tab text (dark only)
The dark scheme was painting green on the school/year/quarter dropdown triggers, on an open dropdown, on the
footer action buttons and on the h3 tab labels. All four now take white / very light gray — `--text` for the
selected or focused state, `--text-soft` for the resting one.

Scope was agreed explicitly: **content links and the active sidebar submenu keep the hue**, and green still
marks active state as a fill or a border, just never as text on those controls. Light mode is untouched — green
text on a light ground is correct there, and it measures 379/379 on its own.

The footer buttons needed a specificity restatement, not a colour swap: `a.BottomButton` is (0,1,0) and
`[data-theme="dark"] a` is (0,1,1), so the anchor rule was winning and every footer label came out green.
Same for `.h3selected a`, which carries `!important` in legacy and therefore needs `!important` back.

### 8.3 Plain pages instead of cards
The lower half of a screen used to arrive inside a bordered, shadowed, 14px-radius plate that also had **its
own scrollbar** — `.list-wrapper { overflow: auto; max-height: min(78vh, 860px) }`. On any list longer than
78vh the table scrolled inside its box while the page still had room, which is what "confined inside a box"
was describing.

`.postbox`, `.list-outer`, `.list-wrapper`, `table.list` and `.widefat` no longer draw a plate: transparent
background, no border, no radius, no shadow. The wrapper keeps `overflow: auto` deliberately — with
`max-height: none` it can never scroll vertically (it grows to fit), but it still contains a wide table
horizontally instead of pushing the whole page sideways. Row banding and header rows are unchanged; only the
outer box is gone. Verified by scrolling the document itself on a search results page (scrollTo(0, 600) lands
at 584).

### 8.4 Action icons: Phosphor, regular, 16px, no pill
Two defects hid here.

**The pill.** `theme.css` styles every `.button` as a 38px text control (`min-height: 38px; padding: 7px 14px;
border; background`). `button()` puts that same class on an `<img>`, so a 150×150 icon asset was laid out in a
**52×38 box with a 22px glyph inside** — the "displayed way too big" report. `img.button` (0,1,1) now
restates icon behaviour: 16×16, no padding, no border, no background, transparent plate. `bigger` is 20px,
`smaller` 14px, and `img.button.cal` neutralises legacy `.button.cal` (0,2,0), which was keeping
`height: 32px` (stretching a 1:1 glyph to 16×32) and a `margin-top: -9px` tuned for that taller box.

**The old artwork.** `button()` prefers `<type>_button.svg`, then `<type>.svg`, then the PNG — but 25 button
types only ever shipped as a PNG, so they kept serving the pre-Phosphor art. `tools/swap-icons.mjs` now writes
those 25 SVGs, and every PNG in `btn/` has an SVG counterpart. Weight was split: sidebar module glyphs stay
`bold` (they read faint at 20px), action icons are **`regular`** per request. Because the artwork is pinned to
`#3c4449` and image buttons no longer sit on the legacy white plate, dark mode lifts them the same way module
icons are lifted: `[data-theme="dark"] img.button { filter: brightness(0) invert(94%) }`.

Note on Phosphor packaging: only `regular` ships unsuffixed files (`plus.svg`); every other weight is
`<icon>-<weight>.svg`.

### 8.5 The duplicate password icon
Every password field showed **two** lock icons, 2 visible per box on My Preferences. Root cause: legacy
declares `.password-hide { display: none }` at (0,1,0), and the theme's text-button rule sits at the very same
(0,1,0) with `display: inline-flex` — concatenated later, so it won and the hide state stopped hiding.
Restated at `img.button.password-hide` (0,2,1) so it wins on specificity rather than source order. The
show/hide plugin toggles with inline styles, which always beat it, so the toggle still works.

### 8.6 Identity card
The card above the sidebar now shows **just the admin's name**, a chevron and today's date. The avatar initial
and the role badge were dropped at the user's request ("just the admin text is enough — why does there have to
be A and Administrator"). The grid lost its `avatar` track, and the `.user-avatar` / `.user-role` rules were
deleted rather than left dead.

### 8.7 Verification
Dark: 379/379 text contrast checks on the target page, 0 failures on Portal, Student Info, Calendars and User
Info on a settled pass. Light: 379/379, tokens untouched (`--bg #eceef0`, `--brand #0e684f`, dropdowns still
green). Eleven pages swept: no PHP errors, no horizontal overflow, no `max-height` traps, no remaining plates,
every `img.button` 16/20/14px. Both schemes were measured on clean reloads — toggling the scheme mid-flight
returns a partly-parsed document, and its computed values are a meaningless mix of both palettes.

## 9. Fourth review round: grayscale dark surfaces, form spacing, sidebar feedback

Scope was agreed in one line: **sidebar text + every page/section/row background + the sidebar active pills go
gray in dark mode; content links and the green *Save* button keep the hue.** Dark mode is now grayscale-only
apart from those two affordances and the semantic status colors (`div.updated` / `div.error`).

### 9.1 Green was leaking from three places
A whole-app audit (every element in `#menu` and `#body`, computed `color` / `background-color` / `border-top-color`
tested for `g > r` and `g > b`) found green in exactly three families on the School pages:

- **Sidebar text.** `[data-theme="dark"] a { color: var(--brand) }` is (0,1,1) and lands *after* `.wp-submenu a`
  (0,1,1), so it beat the submenu's gray and every sidebar link measured `rgb(77,191,149)`.
- **The "New note / New poll" create band.** `table.list:has(.list-add-row [id^="divNoteContent"]) > tbody >
  tr.list-add-row` filled the row with `--brand-50` and ruled it with `--brand`, and its `::before` kicker was
  `--brand-700` — the 716×565 green wash.
- **The School calendar.** Day washes (`--day-full` green, `--day-minutes` amber), calendar event chips
  (`--brand-100` plate + `--brand-700` text + brand left rule) and the `h3.h3selected` green top rule.

All three are restated in the new **theme.css section 27**, uniformly at `html[data-theme="dark"] <selector>`.
That prefix adds one attribute *and* one element, so it out-ranks both the bare legacy selectors and the earlier
same-weight `[data-theme="dark"] …` restatements without relying on concatenation order. `a#selectedModuleLink`
and `a#selectedMenuLink` are the app's own ids, so the grayscale pill and plate reuse them rather than inventing
a parallel class that light mode would have to out-specify.

The calendar's three day types stay **distinguishable**, just as three grays (`#4f4f4f` full, `#3d3d3d`
minutes, `#2f2f2f` no-school) instead of green / amber / gray. Content links (`.md-link`, `.cboxElement`,
`.ethio-onboarding-link`) and the `input.button-primary` fill keep their green; the onboarding **kicker** above
those links is a label, not a link, and now takes `--text-faint`.

### 9.2 Form spacing on every page
Two geometry defects, both visible on Portal Notes and present wherever a file input or a date trio appears:

- **The file input overlapped its label.** `.rt2colorBox > div > label { margin: -4px 0 … }` pulled the "File
  Attached" label up under the control — measured input bottom 891 vs label top 887, a 4px overlap. The `-4px`
  is now `0`, and `input[type="file"] { display: inline-block; vertical-align: top; margin-bottom: 6px }`
  guarantees the gap on any page. Measured gap after: **6px**.
- **The month / day / year selects butted together.** `PrepareDate()` emits `</select><label class="a11y-hidden">
  <select>` with no whitespace, so the three selects sat at gap 0. `#body .nobr > * + * { margin-left: 6px }`
  restores the gap; the interleaved labels are `position: absolute`, so they take the margin without adding a
  phantom gap. Measured gaps after: **6 / 6 / 6** (select → select → select → calendar button).

### 9.3 Sidebar: immediate active state and scroll-to-reveal
Both are in `theme.js`, not CSS.

- **Instant active.** The app marks the current module with `#selectedModuleLink` only on page load, and we
  swallow the module row's click (it just opens the submenu), so the highlight used to lag by a full reload.
  `markModuleSelected()` now *moves that id* onto the clicked row — identical styling, no new CSS, and
  `syncAccordion()` reads the same id so the open state stays in agreement. `#selectedMenuLink` moves the same
  way when a submenu link is clicked, before its navigation.
- **Scroll-to-reveal.** `#menu > .adminmenu` is one scroll port. `revealModule()` nudges `scrollTop` only when
  the row or the first submenu item would land outside the port, and only on a click that opens a module —
  never on load. Measured: scrolled to the bottom then clicking *Students* moved `scrollTop` 405 → 58 and left
  the row and its first item inside the port; clicking *Attendance* from the bottom left its first item visible
  at 218–254 against a port of 156–618.

### 9.4 Verification
Dark mode, computed-style audit of `#menu` and `#body` on 17 module pages (all of School Setup plus the
dashboard, Student/User Fields, Enrollment Codes and Courses): **0 green in the sidebar, 0 green backgrounds**
anywhere. The selected module pill measures `#b0b0b0` / `#2b2b2b` and the selected submenu item `#484848` /
`#f4f4f5` — grayscale, same prominence as the green they replace. The only green left is content links, the
primary button, the `div.updated` success banner (a gray plate with green status text) and the red logout glyph.

One measurement trap to remember: the app adds `#selectedModuleLink` **after** first paint, and `a.menu-top`
transitions its background and colour. In a throttled/occluded tab those transitions freeze at `currentTime 0`,
so the pill reads as the pre-id value (`transparent`) even though the rule is correct. Settle the page first
(`document.getAnimations().forEach(a => a.finish())`) before trusting any computed style here.

Light mode re-checked on a clean reload: tokens untouched, the sidebar's green active submenu and light file
input are unchanged (the geometry fixes land in both schemes because they are corrections, not theming).
