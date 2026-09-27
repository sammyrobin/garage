# GARAGE — by Samuel Torres

![PHP 8.3](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)
![SQLite 3](https://img.shields.io/badge/SQLite-3-003B57?logo=sqlite&logoColor=white)
![No framework](https://img.shields.io/badge/framework-none-141414)
![License: MIT](https://img.shields.io/badge/license-MIT-DD0200)

A web app to catalogue and show off my die-cast car collection (Hot Wheels): a 3D intro,
a filterable gallery, a five-angle viewer per car, statistics, and a control panel that
anyone can explore in **exhibition mode**. It is plain PHP 8.3 + SQLite, built to run on
shared cPanel hosting with no Node, no SSH, no background workers and no database server.

**Live demo:** https://samueltorres.dev/garage · **Control panel:** https://samueltorres.dev/garage/admin

![3D intro: cars floating on a cylinder, then flying into the grid](docs/intro.gif)

> 🇲🇽 **Resumen en español.** GARAGE es la app donde catalogo y exhibo mi colección de autos a
> escala. Tiene una intro 3D con fotos de mis autos, una galería con filtros combinables que
> se reflejan en la URL, un visor de 5 ángulos por auto, estadísticas y un panel de control
> público en modo exhibición: cualquiera puede explorarlo, pero solo el dueño puede guardar
> con su contraseña. Está hecha en PHP 8.3 puro con SQLite (la base de datos es un archivo) para hosting compartido. Las fotos se
> re-codifican a WebP sin metadatos EXIF ni GPS, y cada auto nuevo dispara un correo con el
> estado del disco. Se despliega por FTPS con GitHub Actions junto con mi portafolio.

| Gallery | Car page | Quick-add form | Mobile |
|---|---|---|---|
| ![Gallery](docs/gallery.png) | ![Car page](docs/car.png) | ![Admin form](docs/admin-form.png) | ![Mobile](docs/mobile.png) |

<sub>Screenshots use local demo data with generated drawings instead of real photos.</sub>

## Features

- **3D intro.** Front photos of my cars float on a slowly spinning cylinder, with real depth and
  drag, touch and mouse control. Blurred red, yellow and blue blobs move behind them. On scroll
  or click, the cards fly into the grid (GSAP Flip). It plays once per session, is lighter on
  phones, and is skipped entirely for `prefers-reduced-motion`.
- **Gallery.** Combinable filters: brand chips, series, rarity, condition, year range, text and
  sort. They are all mirrored in the URL, so every view is shareable. Results update live and
  load with infinite scroll; without JS it is a plain GET form.
- **Car page.** A viewer for the angles that exist (front, back, left, right, top), with buttons,
  swipe, arrow keys and a "turn the car" transition. It also has a spec sheet and related cars.
- **Stats.** Totals, top brands chart, rarities, oldest car and Super Treasure Hunts.
  The total invested is private.
- **Exhibition-mode panel.** Anyone can browse it; saving needs the owner password, which opens
  a 30-minute session. Private costs show as `$ •••`. The quick-add form has name, brand + model,
  cost and five photo slots (click, drop or camera); the rest sits under "More details".
  **Save and add another** keeps the flow fast. Full CRUD covers cars, brands and series.
- **CSV import/export.** Import is all-or-nothing and reports errors per line. It accepts Spanish
  or English headers and comma or semicolon. Export is protected against formula injection.
- **New-car e-mail.** Includes the photo, totals and **server disk status**: the cPanel quota bar
  plus the space used by the Garage. Cars saved within 10 minutes are grouped into one summary.
- **Bilingual.** Spanish by default (`/garage/`), English under `/garage/en/`, with localized
  slugs, `hreflang` and a sitemap.

## Stack

| Layer | Choice |
|---|---|
| Backend | PHP 8.3, no framework: front controller, ~80-line router, PDO (prepared statements only) |
| Database | SQLite 3 (one file in `storage/`), numbered transactional SQL migrations |
| Frontend | Server-rendered HTML, CSS custom properties, vanilla JS, GSAP 3 (ScrollTrigger + Flip), self-hosted |
| Images | GD: finfo check → re-encode → WebP 1600/800/400 px, EXIF/GPS stripped |
| Mail | Socket SMTP client (no Composer), HTML + text |
| Fonts | Big Shoulders Display, Outfit, JetBrains Mono (SIL OFL, woff2, self-hosted) |
| Hosting | GoDaddy cPanel shared hosting behind Cloudflare |
| Deploy | Ships with my portfolio over FTPS (GitHub Actions); one-time `setup.php` installer |
| Local | Docker Compose: PHP 8.3 + Apache, Mailpit |

## Architecture

```mermaid
flowchart LR
    V([Visitor]) -->|HTTPS| CF[Cloudflare]
    CF -->|HTTP, CF-Connecting-IP| AP[Apache · /garage/.htaccess]
    AP -->|static| ST[(assets/ · uploads/*.webp)]
    AP -->|everything else| FC[index.php]
    FC --> R{Router}
    R --> PUB[Public controllers<br/>gallery · car · stats · sitemap]
    R --> ADM[Admin controllers<br/>exhibition mode]
    AP -->|once| SET[setup.php<br/>password · then deletes itself]
    R --> MIG[/_migrate<br/>token-protected/]
    ADM --> AUTH[Auth · CSRF · rate limit]
    ADM --> SVC[Services<br/>ImageProcessor · CsvService<br/>NotificationService · DiskStatus]
    PUB --> DB[(SQLite<br/>storage/garage.sqlite)]
    SVC --> DB
    SVC --> UP[(uploads/)]
    SVC -->|SMTP| MAIL[contacto@samueltorres.dev]
    SVC -->|UAPI Quota| CP[cPanel]
    SET --> DB
    GH[GitHub Actions<br/>portfolio deploy] -->|FTPS, code only| AP
```

```
index.php            Front controller
setup.php            One-time installer (panel password → config + folders + tables → disables itself)
.htaccess            Self-contained rules for /garage (does not rely on the portfolio's)
app/Core/            Router, Request/Response, Database, Auth, Csrf, RateLimiter, ClientIp, Migrator…
app/Controllers/     Public, Admin, System (migrations)
app/Services/        ImageProcessor, PhotoStorage, CarService, Catalog, Stats, CsvService, Mailer…
app/Views/           Plain PHP templates (every value goes through e())
app/lang/            es.php, en.php
migrations/          001_…sql → 008_…sql
bin/                 CLI: migrate, set-password, notify (cron)
assets/              CSS, JS, fonts, GSAP
uploads/             Processed photos only (PHP disabled; not in git, never touched by deploys)
storage/             garage.sqlite + logs/ + cache/ (web-blocked, not in git)
```

## Data model

```mermaid
erDiagram
    brands ||--o{ cars : has
    series |o--o{ cars : groups
    cars ||--o{ car_photos : "up to 5 (one per angle)"
    cars ||--o{ notification_queue : announces
    brands { int id PK  string name  string slug  char accent_color }
    series { int id PK  string name  string slug }
    cars {
        int id PK
        string slug UK
        string name
        int brand_id FK
        string model
        numeric cost_mxn "private"
        int series_id FK
        int real_year
        int casting_year
        string collection_number
        text rarity "CHECK list"
        text item_condition "CHECK list"
        bool is_favorite
    }
    car_photos { int id PK  int car_id FK  text angle "front|back|left|right|top"  text file_key UK }
    notification_queue { int id PK  int car_id FK  text queued_at  text claim_token  text sent_at }
    login_attempts { int id PK  text ip_hash "HMAC"  text attempted_at }
```

There is no `users` table. The single owner is represented by a `password_hash()` in
`app/config.php`. Enumerations are `CHECK` constraints, foreign keys are enforced
(`PRAGMA foreign_keys = ON`), triggers keep `updated_at` current, and timestamps are UTC.

## Technical decisions

**Why plain PHP.** The host runs PHP and nothing else I can rely on: no Node, no SSH, no workers.
Without a framework, what I deploy is what runs. The app ships no `vendor/` and has no build
step, and every piece (router, migrations, SMTP, CSRF) is small enough to read in one sitting.
Composer was not necessary.

**Why CSS 3D + GSAP instead of Three.js.** The intro has to end with each card *becoming* a DOM
card in the grid. A WebGL canvas cannot FLIP into HTML elements, but DOM cards can. Each card is a
billboard (it always faces the camera) positioned with `translate3d` on a cylinder, and perspective
gives real depth. Opacity and blur fade the back row. GSAP is about 70 KB, against about 600 KB
for Three.js.

**How photos are processed.**
1. In the browser, a canvas resizes to 2000 px and re-encodes as JPEG. That saves bandwidth and
   already drops EXIF on the phone.
2. On the server, `finfo` checks the real type; the extension and the client MIME are ignored.
   GD decodes the image and applies the EXIF orientation.
3. The image is re-encoded from raw pixels. GD never copies metadata, so GPS, camera serials and
   thumbnails are gone.
4. Three WebP variants are written (1600/800/400 px at about 80% quality), plus a 240 px JPEG of
   the front photo for Outlook, which cannot show WebP.
5. File names are random 128-bit keys; the original is never stored.

A test feeds a JPEG with a real EXIF GPS block and asserts that none of it survives.

**Password before files.** In exhibition mode anyone can fill in the form, so the form first
sends the password alone (a small JSON request). Photos only leave the browser once the password
is accepted. Without JS, the server still checks rate limit → CSRF → password *before* calling
`finfo` or GD. PHP then discards the temporary uploads.

**Why SQLite.** One owner writes, many visitors read, and the whole collection is a few thousand
rows at most. That is exactly SQLite's sweet spot. The database is a single file in `storage/`,
so there is no database server, user or password to manage, and a backup is one file copy. PHP
ships `pdo_sqlite`, so nothing extra is installed. Writes are short transactions with a busy
timeout, which is plenty for one person adding cars.

**Deploying to shared hosting.** The app lives in the `garage/` folder of my portfolio's (private)
repository and ships with the portfolio's own GitHub Actions workflow: SamKirkland/FTP-Deploy-Action
syncs changed files over FTPS to `public_html`. The workflow knows nothing about GARAGE, and it
does not need to:

- **Git decides what can never be touched.** `app/config.php`, `uploads/` and the SQLite file are
  ignored by git. The action only uploads or deletes files it tracks, so a deploy can never
  overwrite the config, delete a photo or reset the database.
- **The server writes its own config.** If `app/config.php` is missing, `setup.php` writes it
  (mode 600) with a `password_hash()` of the panel password (never the password), a random app key
  and a random migrate token. Nothing is uploaded by hand. The SMTP password is not duplicated:
  it is read at runtime from the portfolio's `mail-config.php`, which that deploy generates.
- **Only the owner can install.** Until the config exists, the typed password is checked against
  `app/setup-verifier.php`, a `password_hash()` that lives only in the private deploy repo, so a
  stranger cannot open the page first and choose the panel password. This public repo has no
  verifier: here you either upload a config or add your own verifier.
- **Installing needs no SSH.** `setup.php` asks for the panel password (5 attempts per IP every 15
  minutes), creates `uploads/` with its no-PHP `.htaccess`, creates the database and runs the
  migrations. It then writes `storage/setup.lock` and deletes itself; with the lock in place it
  answers 404 even if a later deploy brings the file back. Later migrations run through
  `POST /_migrate`, which answers 404 without the right `X-Migrate-Token`.

This public repository is a read-only mirror of that folder, and it has no workflow of its own.

**E-mails without cron coupling.** "Save" sends one summary with everything pending; "Save and add
another" only queues. If the tab is closed, whatever is left is sent once 10 minutes pass without
new cars, by a cPanel cron (`bin/notify.php`) or lazily by the next admin page view. An atomic claim
token prevents double sends. SMTP runs after the response is flushed, and a failed e-mail never
blocks saving.

**Disk status.** It comes from cPanel UAPI `Quota::get_quota_info` with an API token that lives
only on the server, cached for 10 minutes. `disk_free_space()` would report the whole shared
server's disk, not my quota. The size of `uploads/` is computed in PHP and cached hourly.

## Security

- All HTML output goes through `e()` (`htmlspecialchars`, `ENT_QUOTES`), and all SQL uses PDO
  prepared statements. Dynamic column lists come only from whitelists.
- The SQLite file lives in `storage/`, which is blocked twice: a rewrite rule answers 404 for the
  folder and `*.sqlite` files are denied anywhere in the tree. It is never in git.
- A CSRF token guards every form. The session cookie is `Secure` + `HttpOnly` + `SameSite=Lax`,
  its ID is regenerated on unlock, and it expires after 30 minutes of inactivity.
- The owner password is limited to **5 failed attempts per real IP every 15 minutes**. The real IP
  is `CF-Connecting-IP`, trusted only when `REMOTE_ADDR` is inside Cloudflare's published IPv4/IPv6
  ranges. IPs are stored as an HMAC and logged masked.
- Headers: a strict CSP (`script-src 'self'`, `style-src 'self'`, no inline code: brand colors come
  from a generated stylesheet), plus `X-Frame-Options: DENY`, `nosniff` and `Referrer-Policy`.
- `app/`, `migrations/`, `storage/`, `bin/`, dotfiles, `*.sql`, `*.sqlite`, `*.md` and config are
  denied by `.htaccess`. `uploads/` has the PHP engine off and serves only `.webp`/`.jpg`.
- Private data is filtered server-side. Cost columns are never selected for public pages, and
  without an owner session the panel receives `null`, not a hidden value.
- Production errors are logged in `storage/logs` and visitors see a generic page.

## Run it locally

Requirements: Docker Desktop.

```bash
git clone https://github.com/sammyrobin/garage.git && cd garage
cp app/config.example.php app/config.local.php    # Docker reads this file (GARAGE_CONFIG)
docker compose up -d --build
docker compose exec -u www-data app php bin/migrate.php        # SQLite file, tables, seeds, uploads/.htaccess
docker compose exec -u www-data app php bin/set-password.php   # owner password (typed hidden)
```

- App: http://localhost:8090/garage/ (English: `/garage/en/`, panel: `/garage/admin`)
- Mailpit (catches every e-mail): http://localhost:8025

For local mail, set `mail.host` to `mail`, port `1025`, `secure` to `''` and an empty username.
The port can be changed with `GARAGE_PORT=8081 docker compose up -d`. Run the CLI as `www-data`
so that Apache can write to the database file.

## Install on shared hosting

1. Upload the code to `public_html/garage/` (in my case, the portfolio deploy does it).
2. Give the installer a way to know it is you, either way:
   - upload `app/setup-verifier.php` containing `<?php return '<password_hash() of your password>';`
     (setup.php then writes `app/config.php` itself), or
   - fill in `app/config.example.php`, set the hash with `php bin/set-password.php` and upload it
     as `app/config.php`.
3. Make sure `pdo_sqlite` is enabled and `garage/app/` and `garage/storage/` are writable (755),
   then open `https://<domain>/garage/setup.php` and enter the panel password. It writes the
   config if needed, installs everything once and deletes itself (and the verifier).
4. cPanel → Cron Jobs, every 5 minutes: `/usr/local/bin/php /home/<user>/public_html/garage/bin/notify.php`
5. Optional: a cPanel API token with the Quota permission in `cpanel.*` adds the disk bar to the
   e-mails; without it they say "n/d".

**Backups.** Download `storage/garage.sqlite` and `uploads/` from the File Manager.

## Roadmap

- [ ] Wishlist of castings I am still hunting
- [ ] Duplicate detection when adding a car (name + series + year)
- [ ] Barcode scan from the blister to prefill the form
- [ ] Public JSON feed of the collection
- [ ] Automated PHPUnit suite in CI (today: end-to-end scripts run locally)

## License

Code under the [MIT License](LICENSE). Photos are not covered. GSAP is used under its own
[standard license](https://gsap.com/standard-license); the fonts are under the SIL OFL.
Hot Wheels is a trademark of Mattel, and car brands are trademarks of their owners. They appear
only as descriptive text; this project is not affiliated with any of them.
