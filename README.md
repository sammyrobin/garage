# GARAGE — by Samuel Torres

A catalogue and showcase for my die-cast (Hot Wheels) car collection, built with plain
PHP 8.3 + MySQL to run on shared hosting. Live at **https://samueltorres.dev/garage** (coming soon).

> 🇲🇽 **Resumen:** GARAGE es la app donde catalogo y exhibo mi colección de autos a escala.
> PHP 8.3 puro, MySQL y JavaScript ligero, pensada para hosting compartido (sin Node ni SSH).
> El panel de control es público en modo exhibición: cualquiera puede explorarlo, pero solo
> el dueño puede guardar cambios con su contraseña.

**Status:** 🚧 in development — phase 1 of 6 (core, routing, database, migrations, owner auth).

## Stack

- **Backend:** PHP 8.3, no framework: front controller, tiny router, PDO with prepared statements.
- **Database:** MySQL 8 with numbered, idempotent SQL migrations.
- **Frontend:** HTML, CSS custom properties, vanilla JS + GSAP (arriving in phase 4).
- **Hosting:** shared cPanel hosting behind Cloudflare. Deployed by GitHub Actions over FTPS.

## Project structure

```
index.php            Front controller (every non-static request lands here)
.htaccess            Self-contained rewrite + deny rules for /garage
app/                 Code: Core/, Controllers/, Views/, lang/, config (web-blocked)
migrations/          001_…sql, 002_…sql (web-blocked)
storage/             logs/ and cache/ (web-blocked, not deployed)
uploads/             Processed WebP photos only; PHP execution disabled
assets/              CSS, JS, fonts
bin/                 CLI helpers for local development (web-blocked)
docker/              Local PHP 8.3 + Apache image
```

## Running locally

Requirements: Docker Desktop.

```bash
cp app/config.example.php app/config.php     # set db.pass to "garage_local"
docker compose up -d --build
docker compose exec app php bin/migrate.php
docker compose exec app php bin/set-password.php   # owner password, typed hidden
```

Open http://localhost:8090/garage/ (Spanish) or http://localhost:8090/garage/en/ (English).
The control panel is at `/garage/admin`.

## Security highlights

- All output escaped with `e()`; all SQL through prepared statements.
- Owner password stored only as a `password_hash()` in the generated config. It is never kept in plain text.
- CSRF token on every form. Session cookie is `HttpOnly`, `SameSite=Lax` and `Secure` in production.
  The session ID is regenerated when the owner unlocks the panel, and the session expires after 30 min of inactivity.
- 5 failed password attempts per real IP every 15 minutes. The real IP comes from `CF-Connecting-IP`,
  trusted only when the request comes from an official Cloudflare range. IPs are stored as an HMAC.
- `Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`.
- `app/`, `migrations/`, `storage/`, `*.sql`, `*.md`, dotfiles and config are blocked from the web.
  PHP execution is disabled in `uploads/`.
- Production errors are logged and never shown to visitors.

## License

Code under the [MIT License](LICENSE). Photos are not covered by the license.
Car brands are trademarks of their respective owners and appear only as descriptive text.
