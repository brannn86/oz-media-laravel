---
name: OZ Media
description: Multi-station broadcast platform in OZ Radio's yellow, black and white, with a scoped Ozubathon event world.
colors:
  oz-yellow: "#f2bd51"
  oz-butter: "#fcf48e"
  oz-black: "#000000"
  oz-white: "#ffffff"
  oz-red: "#d8281b"
  oz-navy: "#141f43"
  oz-ink: "#3a3a3a"
  oz-paper: "#f6f6f4"
  ozb-yellow: "#fdc911"
typography:
  display:
    fontFamily: "TangoSans, Plus Jakarta Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: "clamp(3rem, 8.5vw, 7.5rem)"
    fontWeight: 800
    lineHeight: 0.9
    letterSpacing: "-0.045em"
  headline:
    fontFamily: "TangoSans, Plus Jakarta Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: "4.5rem"
    fontWeight: 800
    lineHeight: 0.95
    letterSpacing: "-0.025em"
  title:
    fontFamily: "TangoSans, Plus Jakarta Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: "2.25rem"
    fontWeight: 800
    lineHeight: 1
    letterSpacing: "-0.025em"
  body:
    fontFamily: "Plus Jakarta Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.0625rem"
    fontWeight: 400
    lineHeight: 1.75
  label:
    fontFamily: "Plus Jakarta Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 700
    lineHeight: 1.25
  ozb-display:
    fontFamily: "Anton, TangoSans, ui-sans-serif, sans-serif"
    fontSize: "3rem"
    fontWeight: 400
    lineHeight: 1
    letterSpacing: "0.025em"
  ozb-body:
    fontFamily: "Poppins, Plus Jakarta Sans, ui-sans-serif, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 600
    lineHeight: 1.25
rounded:
  md: "0.75rem"
  lg: "1rem"
  xl: "1.5rem"
  card: "1.75rem"
  slab: "2rem"
  full: "9999px"
spacing:
  gutter: "16px"
  gutter-wide: "24px"
  grid-gap: "24px"
  section: "80px"
  container: "1280px"
components:
  button-primary:
    backgroundColor: "{colors.oz-black}"
    textColor: "{colors.oz-white}"
    rounded: "{rounded.full}"
    padding: "0 24px"
    height: "48px"
  button-accent:
    backgroundColor: "{colors.oz-yellow}"
    textColor: "{colors.oz-black}"
    rounded: "{rounded.full}"
    padding: "8px 16px"
  button-accent-hover:
    backgroundColor: "{colors.oz-butter}"
    textColor: "{colors.oz-black}"
  button-secondary:
    backgroundColor: "{colors.oz-white}"
    textColor: "{colors.oz-black}"
    rounded: "{rounded.full}"
    padding: "0 24px"
    height: "48px"
  input:
    backgroundColor: "{colors.oz-white}"
    textColor: "{colors.oz-black}"
    rounded: "{rounded.md}"
    padding: "0 16px"
    height: "48px"
  nav-pill-active:
    backgroundColor: "{colors.oz-yellow}"
    textColor: "{colors.oz-black}"
    rounded: "{rounded.full}"
    padding: "8px 16px"
  status-pill:
    backgroundColor: "{colors.oz-black}"
    textColor: "{colors.oz-white}"
    rounded: "{rounded.full}"
    padding: "4px 12px"
  card-dark:
    backgroundColor: "{colors.oz-black}"
    textColor: "{colors.oz-white}"
    rounded: "{rounded.xl}"
    padding: "20px"
  dialog:
    backgroundColor: "{colors.oz-white}"
    textColor: "{colors.oz-black}"
    rounded: "{rounded.slab}"
  ozb-tape:
    backgroundColor: "{colors.oz-black}"
    textColor: "{colors.oz-white}"
    typography: "{typography.ozb-display}"
    padding: "6px 12px"
  ozb-date-sticker:
    backgroundColor: "{colors.ozb-yellow}"
    textColor: "{colors.oz-black}"
    typography: "{typography.ozb-display}"
    padding: "4px 12px"
  ozb-meter:
    backgroundColor: "{colors.oz-black}"
    textColor: "{colors.ozb-yellow}"
    padding: "12px 20px 16px"
  ozb-player-card:
    backgroundColor: "{colors.oz-white}"
    rounded: "{rounded.slab}"
    padding: "16px"
---

# Design System: OZ Media

## Overview

**Creative North Star: "The Station Sticker Wall"**

OZ Media is OZ Radio on the web: a daylight-white page, a warm yellow that behaves like a ground rather than a highlight, solid black for anything that plays, speaks or acts, and heavy TangoSans headlines set tight. The incumbent station world is loud through mass, not ornament: big rounded slabs of yellow and black, full-pill controls, very little line work, and the live player always in the first viewport. The OZ Bali basic site is the same identity at night (navy ground, a yellow sunset glow from below), proving the system is a palette and type pairing plus behaviour, not a single layout.

The Ozubathon event world is a scoped sub-system layered on top of a station while that station's Ozubathon theme is switched on (and on the corporate landing spotlight while any station runs it). It is the 55th-anniversary poster made to work: crumpled white paper torn open over a #fdc911 band printed with ghosted halftone, black tape strips and yellow stickers in Anton capitals, die-cut brand props half off the page, and the 55-hour meter as its signature. It borrows OZ's black, white and red, swaps its yellow and type for the poster's, and leaves every working part (header, player internals, sponsor banner, footer) in the incumbent system.

**Key Characteristics:**
- Yellow, black and white in large solid fields; red only for live state and errors.
- TangoSans extrabold, tight tracking and sub-1 leading for every heading; Plus Jakarta Sans for everything read.
- Controls are full pills; containers are generously rounded slabs (1.5 to 2rem).
- Flat by default; shadows only on things that float (player box, dialogs, menus, the docked mini player).
- Yellow focus rings everywhere (4px, `oz-yellow`).
- Ozubathon only: paper, torn band, rotated square-cornered stickers and tape, Anton capitals, soft paper shadows, one load-time "slap".

## Colors

A three-colour brand (yellow, black, white) with two quiet neutrals, a night navy for the basic site, a live red, and one event-only yellow.

### Primary
- **OZ Marigold** (oz-yellow): the station ground. Fills the on-air hero, the mobile navigation sheet, the active nav pill, accent buttons, link underlines and quote rules, and every focus ring. Also the visualizer and play glyph inside the player, including when Ozubathon is on.
- **Butter Glow** (oz-butter): hover state of yellow buttons and the light end of the audio visualizer gradient. Never a surface.

### Secondary
- **Broadcast Black** (oz-black): the action and media colour. Primary buttons, status pills, the player box, on-air and up-next cards, page rules (2px and 4px borders on the landing page), and all heading ink.
- **On-Air Red** (oz-red): the live dot in status pills, the current hour tick on the Ozubathon meter, and form error text. Nothing decorative.

### Tertiary
- **Ozubathon Gold** (ozb-yellow): the poster's yellow. Only inside the Ozubathon world: the torn band, date sticker, meter numerals and filled ticks, Anton labels on black tape, link underline and focus ring inside the event hero.

### Neutral
- **Pure White** (oz-white): the station page ground, the dialog surface, the Ozubathon player card and sticker borders, text on black.
- **Soft Ink** (oz-ink): body copy, intros and prose on white; headings stay black.
- **Newsprint** (oz-paper): the alternate section ground on the landing page ("From the network").
- **Night Navy** (oz-navy): the OZ Bali basic site's ground, under a radial glow of yellow at 35% fading to transparent.

Black at fixed alphas does the neutral work on white: 10% for hairline borders and dividers, 15% for input and secondary-button strokes, 5% for hover washes, 40 to 70% for secondary text. White at 60 to 85% does the same on black.

### Named Rules
**The Two Yellows Rule.** Marigold (#f2bd51) is OZ; Ozubathon Gold (#fdc911) is the event. Gold appears only inside an Ozubathon surface, and Marigold is never recoloured to Gold, which is why the player's play glyph stays Marigold inside the event hero.

**The Live Red Rule.** Red means "on air now", "this hour" or "this field is wrong". It is never a fill, a heading colour or decoration.

## Typography

**Display Font:** TangoSans (self-hosted; falls back to Plus Jakarta Sans)
**Body Font:** Plus Jakarta Sans (400 to 800, via Bunny Fonts)
**Ozubathon Display:** Anton (400) with TangoSans fallback; **Ozubathon Body:** Poppins (500 to 700)

**Character:** TangoSans is chunky and friendly at heavy weights; Plus Jakarta Sans is a clean geometric reader that carries the rest without competing. Anton is the poster's condensed shout, always in capitals.

### Hierarchy
- **Display** (800, clamp 3 to 7.5rem, 0.9, -0.045em): the corporate landing statement only.
- **Headline** (800, 3rem mobile to 4.5rem, 0.95, tight): page titles via the page heading, with `text-balance`.
- **Title** (800, 1.875 to 2.25rem, 1, tight): section headings; card titles step down to 1.25 to 1.5rem at leading-tight.
- **Body** (400, 1.0625rem, 1.75): CMS prose in Soft Ink, intros at 1.125 to 1.25rem capped near 42rem (`max-w-2xl`).
- **Label** (700, 0.875rem): form labels, status pills, buttons (buttons are 700 at 1rem).
- **Ozubathon Display** (400, 1.125 to 3rem, leading 1, slight positive tracking, uppercase): tape, stickers, meter numerals.
- **Ozubathon Body** (500 to 600, 0.7 to 1.125rem): meter units and captions, spotlight sentence, up-next line.

### Named Rules
**The Heavy-Tight Rule.** Every TangoSans heading is 800 weight with negative tracking and leading at or under 1. A light or loosely set TangoSans heading is off-brand.

**The Capitals-Only Anton Rule.** Anton is always uppercase, leading 1, and only inside the Ozubathon world. Running copy inside the event is Poppins, never Anton.

**The Tabular Clock Rule.** Every changing number (countdowns, the hour counter, live tallies) uses tabular numerals so the layout does not shimmer.

## Layout

One container: centred, max 80rem (`max-w-7xl`) with 16px gutters rising to 24px from `sm`. The basic site narrows to 64rem. Sections breathe at 80px vertical padding (112px from `sm` on the landing page); page headings open at 56 to 80px. Grids use 20 to 24px gaps (`gap-5`/`gap-6`), 32 to 48px between hero columns.

The station hero puts the player first: on phones it is the first block after the header and the sponsor banner sits directly under it; from `lg` the player takes the wide column (with on-air and up-next cards at 18 to 20rem beside it). Program artwork pages through a two-up (phones) / three-up (`lg`) snap carousel. The header is sticky, 64px, white at 95% with backdrop blur.

Ozubathon hero: a 5/12 to 7/12 split from `lg` (lockup over meter on the left, player spanning both rows on the right, sponsor banner under the player); on phones lockup, player, meter, banner in that order. The torn band sits across roughly the middle two thirds and is positioned per surface. The landing spotlight is a 1:1 split with the compact lockup.

**The Player-Above-The-Fold Rule.** On every station home, themed or not, the play control is in the first viewport at every breakpoint, and nothing decorative overlaps its controls.

## Elevation & Depth

The incumbent system is flat and tonal: depth comes from solid yellow and black fields against white, hairline 10% borders, and the black player box sitting on yellow. Shadows are reserved for things that float above the page: the player box (`shadow-xl`), the docked video mini player and dialogs (`shadow-2xl`), the account menu and player bar (`shadow-xl`), the location popover (`shadow-lg`). Dialog overlays are black at 70% with a small backdrop blur.

The Ozubathon world adds paper depth: soft, dark, negatively spread shadows that read as a sticker lifted slightly off paper, plus a grey drop shadow under the logo.

### Shadow Vocabulary
- **Float** (`shadow-xl` / `shadow-2xl`, Tailwind defaults): player box, dialogs, menus, mini player.
- **Sticker lift** (`box-shadow: 0 8px 16px -6px rgba(0,0,0,0.45)`, 0.35 to 0.5 alpha): Ozubathon tape and stickers.
- **Tape lift** (`box-shadow: 0 10px 20px -8px rgba(0,0,0,0.5)`): the up-next tape and the spotlight caption card.
- **Meter lift** (`box-shadow: 0 14px 30px -12px rgba(0,0,0,0.55)`): the 55-hour meter.
- **Card lift** (`box-shadow: 0 22px 45px -18px rgba(0,0,0,0.55)`): the white die-cut card around the Ozubathon player.
- **Logo drop** (`filter: drop-shadow(0 10px 14px rgba(0,0,0,0.28))`): the Ozubathon logo on its burst.

### Named Rules
**The Float-Only Rule.** Outside Ozubathon, a shadow means the element floats over the page (overlay, dock, menu, player). Resting cards and tiles are flat.

**The Soft Paper Rule.** Ozubathon shadows are always blurred with negative spread. No hard offset shadows anywhere in either world.

## Shapes

OZ is round: every control is a full pill (buttons, nav items, status pills, carousel dots, mobile menu trigger), inputs are 12px-rounded, and containers are large soft slabs: 24px for the player box and on-air cards, 28px for program artwork, 32px for dialogs and the Ozubathon player card, 16px for prose images and focusable logos. Lines are rare and heavy when used: 2px and 4px black rules on the landing page, a 4px yellow rule on prose quotes.

Ozubathon reverses the corner language for its own pieces: tape strips, stickers and the meter are square-cornered and rotated (text pieces between -4deg and +1.5deg, the meter -1deg, props 6 to 14deg), their edges torn by authored SVG masks (the band and each meter tick). The player card keeps OZ's 32px rounding and is never rotated.

**The Straight Player Rule.** In the Ozubathon world everything may tilt except the player card and its controls.

## Components

### Buttons
Confident, chunky pills.
- **Shape:** full pill, 48px tall (56px for the landing hero CTA).
- **Primary:** Broadcast Black with white 700 text, 24px side padding; hover scales to 1.01 to 1.02.
- **Accent:** Marigold with black text (the "Full schedule" and landing "Listen" actions); hover to Butter Glow.
- **Secondary:** transparent with a 2px black-15% stroke; hover darkens the stroke to black.
- **Text button:** bold black with a 3px Marigold underline offset 4px; hover turns the underline black.
- **Focus:** 4px Marigold ring, no outline (black-30% ring on yellow grounds).

### Status pill
A black pill with a 8px dot (red when on air, white-40% when off) and bold white text, sitting in the player header. Reused unchanged inside the Ozubathon hero, where "Ozubathon live" replaces "Off air" between shows during the 55 hours.

### Cards / Containers
- **Corner Style:** 24px (on-air, up-next), 28px (program art), 32px (dialogs).
- **Background:** Broadcast Black for live-broadcast cards, Marigold behind program artwork, white for dialogs.
- **Shadow Strategy:** flat, except dialogs (Float).
- **Internal Padding:** 16 to 20px.
- Program tiles lift 4px on hover and zoom their artwork 5% over 500ms.

### Inputs / Fields
- **Style:** 48px tall, white, 2px black-15% stroke, 12px radius, 16px padding, Plus Jakarta 1rem.
- **Focus:** stroke turns black plus a 4px Marigold-60% ring.
- **Error:** 0.875rem semibold On-Air Red text under the field. Labels are 0.875rem bold above.

### Navigation
Sticky white header with the station logo at left. Desktop items are pill links in 0.875rem semibold black-70%; hover adds a black-5% wash; the active page is a Marigold pill. On phones a right-side sheet in solid Marigold lists items in TangoSans 1.875rem extrabold.

### Player
The persistent HeroPlayer: a 16:9 black box with 24px corners and Float shadow, header row (status pill plus show) above, controls to the right. It docks to a 24rem mini player bottom-right on scroll. Both worlds use it unchanged.

### Ozubathon Lockup (event only)
The 55 Ozubathon logo over the halftone burst (rotated -6deg) with two black four-point sparkles carrying a white stroke, then a black tape in Anton white ("On-air · Online · On-ground", -4deg), a Gold date sticker with a 3px white border (-3deg), and on the large size a white strip with Gold Anton outlined by a 5px black text stroke (+1.5deg). A compact size serves the landing spotlight.

### Ozubathon Meter (event only, signature)
A black slab (Meter lift, -1deg) with a pocket-watch prop at its corner. Before the start: a Gold Anton countdown (days, hrs, min, sec) with Poppins units. During: a red pulsing "Live" dot, "Hour N" in Gold, "of 55" in white-70%. After: a Gold thank-you line. Below, 55 torn-tape ticks in a single row (every fifth taller, like a ruler): Gold when done, red and pulsing for the current hour, white-20% ahead. The pulse respects reduced motion.

### Ozubathon Ground and Player Card (event only)
The ground is the crumpled-paper photograph (fallback #f1f0ec) with a torn band of Gold multiplied with a ghosted halftone plate and the paper's grain. The player sits on a white 32px-rounded card with 12 to 16px padding and Card lift; the Ozzy-with-boombox prop leans on its outer lower corner, outside the video. The up-next line becomes a rotated black tape with a Gold Anton "Up next" and a Gold-underlined schedule link.

### Motion
Incumbent: floating reactions rise and fade over 2.5s; announcer names marquee in a loop; program pages auto-advance every 5s and pause on hover or focus. Ozubathon: each sticker and prop "slaps" on once at load (520ms, `cubic-bezier(0.16, 1, 0.3, 1)`, from 1.12 scale, 35% opacity, 3px blur), staggered 0 to 520ms. All of it stops under reduced motion.

## Do's and Don'ts

### Do:
- **Do** set every station heading in TangoSans 800 with negative tracking and leading at or under 1.
- **Do** use Marigold (#f2bd51) as a ground and for every focus ring (4px), and black for anything that acts or plays.
- **Do** make controls full pills and containers 24 to 32px slabs.
- **Do** keep the play control in the first viewport on every station home, with the sponsor banner directly beneath the player.
- **Do** confine Ozubathon Gold (#fdc911), Anton, Poppins, the paper ground and rotation to surfaces where a station's Ozubathon theme is on.
- **Do** use tabular numerals for clocks, countdowns and live tallies.
- **Do** ship every Ozubathon raster under public/images/ozubathon with its provenance sidecar.

### Don't:
- **Don't** put a shadow on a resting card in the station world; shadows are for floating layers.
- **Don't** use hard offset shadows in either world; Ozubathon depth is soft, blurred and negatively spread.
- **Don't** rotate the player card or let a prop cover the player's controls or video.
- **Don't** set running copy in Anton, or Anton in lowercase.
- **Don't** use red as decoration; it marks live state, the current hour, and errors only.
- **Don't** recolour the player, header, footer or sponsor banner for Ozubathon; the theme wraps them, it does not reskin them.
