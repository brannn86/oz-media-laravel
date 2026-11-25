# OZ Media

The OZ Radio network platform: a multi-station CMS with an admin panel, public station sites with a persistent live player, and a JSON API shared by the website and the mobile app.

Each station (e.g. `oz-bandung`) gets its own site at `/{station-slug}` with:

- a **live audio/video player** that keeps playing across page navigation
- **now playing** and recently played songs, read from the station's Icecast stream
- **live chat**, **song requests**, **live polls** and **reactions** over WebSockets
- **OZ Poin** loyalty points for listening, with streaks, a leaderboard and rewards
- programs, schedules, announcers, news, events and social highlights managed in the admin

## Stack

| Layer    | Tech                                                                                    |
| -------- | --------------------------------------------------------------------------------------- |
| Backend  | PHP 8.3+ (developed on 8.5), Laravel 13, Fortify, Sanctum, Socialite (Google)           |
| Admin    | Filament 5 at `/admin`, Filament Shield + spatie/laravel-permission for roles           |
| Frontend | Inertia 3, React 19, TypeScript, Tailwind CSS 4, Vite+ (`vp`), Wayfinder (typed routes) |
| Realtime | Laravel Reverb + Laravel Echo                                                           |
| Data     | PostgreSQL, Redis (cache, sessions, queues, rate limits, locks)                         |
| Media    | spatie/laravel-medialibrary, images converted to WebP on a queue                        |
| Ops      | Laravel Pulse at `/pulse`, optional self-hosted Umami analytics                         |
| Tests    | Pest 5, Larastan, Pint                                                                  |

## Getting started

### Requirements

- PHP 8.3+ with the `pgsql`, `redis`/`predis`, `gd` or `imagick`, `intl` and `bcmath` extensions
- Composer, Node.js 20+ and npm
- PostgreSQL and Redis running locally

Laravel Herd works but isn't required: any PHP binary will do. If Herd is installed but its app isn't running, call its binary directly (on Windows: `%USERPROFILE%\.config\herd\bin\php85\php.exe`).

### First-time setup

```bash
git clone https://github.com/ozradiotech/oz-media-laravel.git
cd oz-media-laravel

cp .env.example .env              # then edit DB_*, REDIS_* and REVERB_* (see below)
createdb oz_media_laravel         # or create it in your Postgres GUI

composer setup                    # composer install, key:generate, migrate, npm install, npm run build
php artisan db:seed               # roles, OZ Bandung station + programs, rewards, admin user
php artisan storage:link          # serves uploaded images
```

Fill in `.env` before running `composer setup`:

- `DB_*`: your PostgreSQL credentials (database `oz_media_laravel` by default).
- `REVERB_APP_ID`, `REVERB_APP_KEY` and `REVERB_APP_SECRET`: any random strings locally. The `VITE_REVERB_*` values copy them automatically.
- `APP_URL`: the URL you open the site on, e.g. `http://127.0.0.1:8000`, or `https://oz-media-laravel.test` with Herd.

### Run it

```bash
composer dev
```

This runs everything in one terminal:

| Process   | Command                                                                            |
| --------- | ---------------------------------------------------------------------------------- |
| server    | `php artisan serve` (skip it if Herd/Valet serves the site)                        |
| vite      | `npm run dev`                                                                      |
| reverb    | `php artisan reverb:start` (WebSockets on port 8080)                               |
| queue     | `php artisan queue:listen --queue=mail,points,media,analytics,maintenance,default` |
| scheduler | `php artisan schedule:work` (now-playing sync, listener sampling, cleanup)         |

Then open:

| URL           | What                                                    |
| ------------- | ------------------------------------------------------- |
| `/`           | Corporate landing page (network of stations)            |
| `/oz-bandung` | The OZ Bandung station site                             |
| `/admin`      | Admin panel                                             |
| `/pulse`      | Server health, queues and slow requests (dev role only) |

### Local accounts (after `db:seed`)

All passwords are `password` locally.

| Email                       | Role           | Can do                                                                             |
| --------------------------- | -------------- | ---------------------------------------------------------------------------------- |
| `admin@ozmedianet.com`      | `dev`          | Everything (super admin). Change it with `ADMIN_EMAIL` / `ADMIN_PASSWORD`.         |
| `editorial@ozmedianet.test` | `editorial`    | Programs, schedules, announcers, news, Studio Mode                                 |
| `social@ozmedianet.test`    | `social_media` | Social highlights, events, rewards and redemptions, video live toggle, Studio Mode |

In production the `dev` account gets a random password unless `ADMIN_PASSWORD` is set. Listeners sign up on a station site (email or Google) and have no admin access.

## Useful commands

| Command                                      | Does                                                                        |
| -------------------------------------------- | --------------------------------------------------------------------------- |
| `composer dev`                               | Run the whole app locally (see above)                                       |
| `composer test`                              | Pint check, Larastan, then the full Pest suite                              |
| `composer ci:check`                          | Frontend check + TS types + `composer test`                                 |
| `composer lint`                              | Fix PHP formatting with Pint                                                |
| `php artisan test --compact`                 | Pest only (add a path or `--filter` to narrow it)                           |
| `npm run check` / `npm run check:fix`        | Frontend lint and format (Vite+)                                            |
| `npm run types:check`                        | TypeScript check                                                            |
| `npm run build`                              | Production build of the frontend                                            |
| `php artisan wayfinder:generate --with-form` | Regenerate typed routes in `resources/js/routes` and `resources/js/actions` |

If a page 500s with _"Unable to locate file in Vite manifest"_ or TypeScript says a route doesn't exist, the generated files are stale. Run `npm run build` (or keep `composer dev` running) and `wayfinder:generate`.

### OZ commands

| Command                                 | Schedule        | Does                                                                                                                                                      |
| --------------------------------------- | --------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `stations:sync-now-playing {station?*}` | every 15 s      | Reads each station's Icecast `status-json.xsl`, records song changes in `track_plays`, pushes them over Reverb and marks matching song requests as played |
| `oz:sample-listeners`                   | every minute    | Stores each station's daily peak listener count for the dashboard                                                                                         |
| `oz:prune-redis-keys`                   | hourly          | Deletes heartbeat keys in Redis that lost their TTL                                                                                                       |
| `oz:archive-chat [--now]`               | daily 03:30 WIB | Moves chat older than 90 days to `chat_message_archives`, purges deleted chat after 30 days                                                               |
| `oz:mail-test {to}`                     | manual          | Sends one test email right away with the current mail settings and shows the SMTP error if it fails                                                       |

## Project layout

```
app/
  Actions/        Business logic, one class per use case (Chat, Points, Polls, SongRequests, NowPlaying, ...)
  Console/        OZ artisan commands
  Events/         Broadcast events (chat, polls, requests, reactions, now playing, stream status)
  Filament/       Admin panel: Resources, Pages (Studio Mode, Station Settings, Muted Listeners), Widgets
  Http/
    Controllers/Api/V1/   JSON API
    Controllers/Public/   Station site pages (Inertia)
    Middleware/ShareStationContext.php   Shares station, broadcast and now playing with every station page
    Resources/            API / Inertia JSON shapes
  Jobs/           Queued work (AwardPoints, RecordPostView, ArchiveOldChatMessages)
  Models/         Eloquent models (Station is the root of almost everything)
  Support/        StationCache, Icecast client, profanity filter, ...
config/oz.php     All OZ-specific settings (points, chat, requests, queues, retention, now playing)
resources/js/
  pages/          Inertia pages (station/*, corporate, auth, settings)
  components/     player/, chat/, live/, station/, auth/, ui/
  layouts/StationLayout.tsx   Keeps the player mounted between pages
  routes/, actions/           Wayfinder output (generated, gitignored)
routes/
  web.php         Corporate page, Google OAuth, station sites (/{station}/...)
  api.php         API v1
  console.php     Scheduled tasks
tests/Feature/    Pest tests, including Hardening/ (RBAC matrix, anti-cheat, voting integrity, ...)
```

Conventions:

- Controllers stay thin and call an **Action** in `app/Actions`. The website and the API share the same actions.
- Everything a station shows hangs off `Station`. Station slugs must contain a hyphen (`oz-bandung`), which keeps them from colliding with fixed paths like `/admin`.
- Times use **WIB** (`Asia/Jakarta`): schedules, the OZ Poin day and streaks.

## Web routes

| Path                                           | Page                                               |
| ---------------------------------------------- | -------------------------------------------------- |
| `/`                                            | Corporate landing page                             |
| `/{station}`                                   | Station home: on air, now playing, recently played |
| `/{station}/programs`                          | Programs and schedule                              |
| `/{station}/announcers`                        | Announcers                                         |
| `/{station}/events`                            | Events                                             |
| `/{station}/rewards`                           | OZ Poin rewards and leaderboard                    |
| `/{station}/news`, `/{station}/news/{slug}`    | OZ News                                            |
| `/{station}/about`, `/{station}/privacy`       | Static pages                                       |
| `/{station}/profile`                           | Listener profile (signed in)                       |
| `/login`, `/register`, `/forgot-password`, ... | Fortify auth (station sites open these in a popup) |
| `/auth/google/redirect`                        | "Continue with Google"                             |
| `/settings/*`                                  | Account settings                                   |

## API v1

Base URL: `/api/v1`. All responses are JSON.

**Auth:** Sanctum. The website calls the API with its session cookie. The mobile app sends `Authorization: Bearer <token>` using the token from `/auth/login`, `/auth/register` or `/auth/google`. 🔒 marks endpoints that need a signed-in user. Throttles are per minute.

### Auth

| Method | Path                 | Body                                                                | Notes                                       |
| ------ | -------------------- | ------------------------------------------------------------------- | ------------------------------------------- |
| POST   | `/auth/register`     | `name`, `email`, `password`, `password_confirmation`, `device_name` | 201 with `{ token, token_type, user }`      |
| POST   | `/auth/login`        | `email`, `password`, `device_name`                                  | `{ token, token_type: "Bearer", user }`     |
| POST   | `/auth/google`       | `access_token` (Google), `device_name`                              | Native Google sign-in                       |
| POST   | `/auth/logout` 🔒    | none                                                                | Revokes the current token, 204              |
| GET    | `/me` 🔒             | none                                                                | The signed-in account (points, streak, ...) |
| GET    | `/me/points` 🔒      | none                                                                | Points ledger, paginated by 30              |
| GET    | `/me/redemptions` 🔒 | none                                                                | Last 50 reward redemptions                  |

The auth endpoints are throttled to 10 requests a minute.

### Station: `/stations/{slug}`

Inactive stations return 404.

| Method | Path                       | Body / query                                | Throttle | Returns                                                                                                                                        |
| ------ | -------------------------- | ------------------------------------------- | -------- | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| GET    | `/`                        | none                                        |          | Station details + chat settings (`data`), program on air (`broadcast`)                                                                         |
| GET    | `/now-playing`             | none                                        |          | Program on air (`data`) + `now_playing: { track, recent[] }`                                                                                   |
| GET    | `/chat`                    | none                                        |          | Last 50 messages, pinned message, settings, `me`                                                                                               |
| POST   | `/chat/messages` 🔒        | `body` (max 280)                            | 30       | The message (201). Profanity is masked; slow mode and mutes apply                                                                              |
| POST   | `/listeners/auth`          | `socket_id`, `channel_name`                 | 30       | Presence-channel auth for the live listener count (guests allowed)                                                                             |
| POST   | `/heartbeat` 🔒            | none                                        | 10       | OZ Poin: call every 60 s while playing. Returns `accepted`, `awarded`, `points_balance`, `streak_days`, `daily_cap_reached`, `next_in_seconds` |
| GET    | `/requests`                | none                                        |          | Request queue + your limits                                                                                                                    |
| POST   | `/requests` 🔒             | `song_title`, `artist`, `shoutout_message?` | 10       | The request (201)                                                                                                                              |
| POST   | `/requests/{id}/upvote` 🔒 | none                                        | 60       | Toggles your upvote                                                                                                                            |
| GET    | `/polls/current`           | none                                        |          | The open poll (and your vote), or null                                                                                                         |
| POST   | `/polls/{id}/votes` 🔒     | `option_id`                                 | 20       | The updated poll. One vote per listener                                                                                                        |
| POST   | `/reactions`               | `type`: `fire` \| `clap` \| `heart`         | 120      | 204. Also limited to 3 every 2 s per listener                                                                                                  |
| GET    | `/rewards`                 | none                                        |          | Rewards available on this station                                                                                                              |
| POST   | `/rewards/{id}/redeem` 🔒  | `note?` (max 500)                           | 10       | The redemption (201)                                                                                                                           |
| GET    | `/leaderboard`             | `?period=week\|all`                         |          | Top listeners by points                                                                                                                        |

Validation errors use Laravel's standard 422 shape: `{ message, errors: { field: [..] } }`.

## Realtime (Reverb)

Public channels, one set per station. The client listens with Echo using the dotted event name, e.g. `.chat.message.sent`.

| Channel                    | Events                                                                                      |
| -------------------------- | ------------------------------------------------------------------------------------------- |
| `station.{slug}`           | `stream-status.updated` (live/offline, video live), `now-playing.updated`                   |
| `station.{slug}.chat`      | `chat.message.sent`, `chat.message.deleted`, `chat.message.pinned`, `chat.settings.updated` |
| `station.{slug}.requests`  | `song-request.updated`                                                                      |
| `station.{slug}.polls`     | `poll.updated`                                                                              |
| `station.{slug}.reactions` | `reaction.sent`                                                                             |
| `station.{slug}.listeners` | Presence channel for the live listener count (authorized by `/listeners/auth`)              |

Broadcasting fails safe: if Reverb is down, the action still succeeds and the event is just not delivered.

## How the main features work

**OZ Poin.** The player sends one heartbeat a minute while audio or video is playing. A Redis lock per _account_ admits one heartbeat per interval, so extra tabs or devices earn nothing. Listening pays 1 point a minute, up to 120 a WIB day. The first listen of the day pays a streak bonus (5 × streak days, up to 7 days). Approved song requests pay 25 and poll votes pay 5. All ledger writes go through the `AwardPoints` job on the `points` queue, so **points only show up when a queue worker is running**. Values are in `config/oz.php` → `points`.

**Now playing.** `stations:sync-now-playing` derives the Icecast status URL from the station's `stream_audio_url`. The admin can set whether the stream title reads "Title - Artist" or "Artist - Title" (Station Settings).

**Caching.** Public station data is cached in Redis through `App\Support\StationCache`. Cache keys include a per-station version, and `StationContentObserver` bumps that version whenever a station, program, schedule, announcer, post, event or social highlight changes. Edits in the admin show up immediately, with no manual cache clearing.

**Images.** Uploads go through Media Library and are converted to WebP on the `media` queue. Older records fall back to their original image column until re-uploaded.

**Admin roles.** `dev` is Shield's super admin. `editorial` and `social_media` have fixed permission sets defined in `App\Enums\AdminRole`. Built-in role names can't be renamed. `tests/Feature/Hardening/RbacMatrixTest.php` pins down who can do what.

**Studio Mode.** The view for the on-air studio: live chat moderation, the song request queue, polls, reactions and the listener count. It has its own full-width panel (`studio.ozmedianet.com`, or `/studio` locally), linked from the admin sidebar, with the same accounts and permissions.

**Addresses.** With `MAIN_DOMAIN`, `ADMIN_DOMAIN` and `STUDIO_DOMAIN` set, the public site, admin and studio each answer only on their own host. When they're empty (locally), everything runs on one host: admin at `/admin` (dashboard at `/admin/dashboard`) and studio at `/studio`.

## Configuration reference

Beyond the standard Laravel keys in `.env.example`:

| Key                                                                                 | Purpose                                                                                   |
| ----------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------- |
| `ADMIN_EMAIL`, `ADMIN_PASSWORD`                                                     | First `dev` admin created by the seeder                                                   |
| `MAIN_DOMAIN`, `ADMIN_DOMAIN`, `STUDIO_DOMAIN`                                      | Host for the public site, admin and studio (empty: one host, `/admin` and `/studio`)      |
| `SESSION_DOMAIN`                                                                    | `.ozmedianet.com` in production, so one sign-in works across the subdomains              |
| `ICECAST_ADMIN_USER`, `ICECAST_ADMIN_PASSWORD`                                      | Optional Icecast admin login, for the listener player breakdown in Analytics → Devices    |
| `REVERB_*`, `VITE_REVERB_*`                                                         | WebSocket server and the client's connection to it                                        |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`                   | "Continue with Google" (hidden when unset)                                                |
| `UMAMI_SCRIPT_URL`, `UMAMI_WEBSITE_ID`                                              | Adds the Umami tracking script to station sites                                           |
| `UMAMI_SHARE_URL`                                                                   | Umami share link embedded in the admin dashboard                                          |
| `ADVERTISING_EMAIL`                                                                 | Where the house ad under the player sends advertisers (default `marketing@ozmedianet.com`) |
| `QUEUE_MAIL`, `QUEUE_POINTS`, `QUEUE_MEDIA`, `QUEUE_ANALYTICS`, `QUEUE_MAINTENANCE` | Rename the named queues (defaults: `mail`, `points`, `media`, `analytics`, `maintenance`) |

App-level tuning (chat limits, blocked words seed, points, request limits, retention) lives in `config/oz.php`.

## Email

The app sends two emails: the **password reset** link and the sign-up **email verification**. Both are queued on the `mail` queue, so a slow or unreachable mail server never blocks sign-up or "forgot password". They only go out when a queue worker is running. A reset requested from a station page links back to that page (`?return=`).

Locally, `MAIL_MAILER=log` writes emails to `storage/logs/laravel.log` instead of sending them.

Production sends through SMTP as **OZ Media <no-reply@ozmedianet.com>**:

```dotenv
APP_NAME="OZ Media"                     # shown in the email header and sign-off
APP_URL=https://your-public-domain      # links in emails are built from this
MAIL_MAILER=smtp
MAIL_SCHEME=null                        # leave null: 587 uses STARTTLS, 465 uses SSL automatically
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="no-reply@ozmedianet.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Then check it from the server: `php artisan oz:mail-test you@example.com`.

So emails land in the inbox rather than spam, `ozmedianet.com` needs these DNS records (your SMTP provider gives you the exact values):

- **SPF**: a TXT record authorizing the SMTP server to send for the domain
- **DKIM**: the signing key TXT record from the provider
- **DMARC**: e.g. `_dmarc.ozmedianet.com TXT "v=DMARC1; p=none; rua=mailto:postmaster@ozmedianet.com"` to start, tightened to `quarantine` once reports look clean

`APP_NAME` is also part of the Redis cache and session key prefixes, so changing it on a running server signs everyone out once and starts with a cold cache.

## Testing

```bash
composer test                     # pint --test, phpstan, pest
php artisan test --compact        # pest only
php artisan test --compact tests/Feature/Hardening
```

Tests run on in-memory SQLite with the array cache set to serialize like Redis, so Redis and Postgres aren't needed. `phpunit.xml` turns off PHP's garbage collector because PHP 8.5.12 can corrupt the heap on long single-process runs.

## Deploying to production

On every deploy:

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder --force   # permissions for new admin pages; safe to re-run
php artisan db:seed --class=OzBaliSeeder --force             # creates OZ Bali (/oz-bali, basic site) if missing; safe to re-run
php artisan optimize
php artisan filament:optimize
php artisan queue:restart
```

Then restart Reverb (`php artisan reverb:restart`) so it loads the new code.

Keep these processes running (Supervisor, systemd or Forge daemons):

| Process       | Command                                                                                                                                |
| ------------- | -------------------------------------------------------------------------------------------------------------------------------------- |
| Queue workers | `php artisan queue:work --queue=mail,points,media,analytics,maintenance,default` (or one worker per queue to scale them independently) |
| WebSockets    | `php artisan reverb:start` behind your web server's proxy                                                                              |
| Scheduler     | cron: `* * * * * php /path/to/artisan schedule:run` (it stays up for the minute to run the 15-second now-playing sync)                 |

Production `.env` checklist: `APP_ENV=production`, `APP_DEBUG=false`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis`, SMTP mail (see [Email](#email)), `ADMIN_PASSWORD` for the first seed, and `REVERB_HOST`/`REVERB_SCHEME` set to the public WebSocket host. Run `php artisan storage:link` once.

Domains:

- `.env`: `APP_URL=https://ozmedianet.com`, `MAIN_DOMAIN=ozmedianet.com`, `ADMIN_DOMAIN=admin.ozmedianet.com`, `STUDIO_DOMAIN=studio.ozmedianet.com`, `SESSION_DOMAIN=.ozmedianet.com`, `SESSION_SECURE_COOKIE=true`.
- DNS: `A`/`CNAME` records for `admin` and `studio` pointing at the same server as `ozmedianet.com`, and the web server (and TLS certificate) serving all three hosts from the same `public/` directory.
- Google sign-in: the redirect URI stays `https://ozmedianet.com/auth/google/callback`.

PHP: `upload_max_filesize` and `post_max_size` of at least `25M` (the intermission video upload allows 20 MB), and the web server's body limit to match (nginx `client_max_body_size 25m`).
