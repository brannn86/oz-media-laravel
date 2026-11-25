# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **Listeners & community members:** young urban audiences (especially OZ Radio Bandung) tuning into live audio/video streams, interacting via live chat, song requests, polls, and OZ Poin rewards, and celebrating OZ Radio's 55th anniversary event (**Ozubathon**).
- **Station & editorial staff:** admins (`dev`, `editorial`, `social_media`) managing stations, live streams, schedules, content, and toggling the exclusive Ozubathon event theme in Filament (`Station Settings`).

## Product Purpose

OZ Media is a multi-station digital broadcast platform combining persistent live audio/video streaming, interactive community features, editorial news/events, and a corporate network portal (`/`). The Ozubathon theme turns a station site (`/{station}`) and a corporate spotlight (`/`) into an event-grade broadcast hub for OZ Radio's 55th anniversary 55-hour subathon (**23–25 December 2026**, **"55 - Loud and Live"**).

## Positioning

Unlike generic radio websites or static event microsites, OZ Media unites a persistent cross-page audio/video player, live WebSocket listener count/reactions/chat, and show schedules directly inside an authentic retro-urban zine poster world built from OZ Radio's own halftone collage and mascot assets.

## Operating Context

- Broadcast timezone is WIB (`Asia/Jakarta`, UTC+7).
- Ozubathon runs for 55 continuous hours starting **23 December 2026** (00:00 WIB to 25 December 2026 07:00 WIB), spanning On-Air, Online, and On-Ground activities.
- Admins toggle the exclusive Ozubathon theme per station in Filament (`Station Settings`), while `StationCache` caches station props and flushes on save.

## Capabilities and Constraints

- Tech stack: Laravel 13, Inertia 3, React 19, TypeScript, Tailwind CSS 4, Vite+, Filament 5.
- Persistent `PlayerProvider` and `HeroPlayer` must keep working across radio/video switching, floating reactions, Icecast now-playing, and mini-player scroll docking.
- `brand/` is gitignored; shipping assets must live under `public/images/ozubathon/`.
- Theme must remain toggleable off/on cleanly without breaking standard station pages, basic stations, or existing tests.

## Brand Commitments

- **OZ Radio Bandung Brand Guideline (2026):** Friendly, Creative, Collaborative, Culturally Relevant, Progressive; `TangoSans` display + `Plus Jakarta Sans` body; `#F2BD51` yellow, `#000000` black, `#FFFFFF` white, `#D8281B` red.
- **Ozubathon Moodboard & Poster:** Retro-meets-urban zine collage combining halftone & grain textures, crumpled torn paper, sticker-bordered vintage broadcast props, `#FDC911` / `#000000` / `#FFFFFF` high-contrast palette, `EXTENDA 30 DECA` / condensed bold display + `Poppins` secondary type, and the official `55 Ozubathon - Loud and Live` logo.

## Evidence on Hand

- Official `55 Ozubathon` logo (`brand/logo/ozubathon.svg`, `ozubathon.png`).
- Halftone sticker & texture cutouts (`brand/assets/burst.svg`, `Paper.svg`, `Picture1.svg`–`Picture8.svg`).
- Ozzy mascot poses (`brand/ozzy/*`).
- Reference poster (`brand/ozubathon poster.png`) and moodboard (`brand/Moodboard OZSUBATHON.pdf`).

## Product Principles

1. **Tune in first:** Even in full festival takeover mode, starting the live radio or video stream takes one clear tap above the fold.
2. **Authentic craft over generic skinning:** Use the actual halftone cutouts, torn paper textures, and poster typography rather than slapping a banner onto a standard layout.
3. **Zero broken persistence:** Audio, video dock, live chat, sponsor banner, and now-playing updates work identically whether Ozubathon theme is on or off.
4. **Accessible at full volume:** High-contrast black-on-yellow / white-on-black ink, reduced-motion fallbacks, tabular countdown numerals, and keyboard focus rings.
