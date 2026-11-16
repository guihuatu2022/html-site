# PHP Blog Web Foundation

This directory is the new application foundation for the rewrite of the static personal site into a lightweight PHP blog system.

## Architecture goals

- Keep the current HTML prototype as a visual reference only.
- Build the real application under `web/` without disturbing the original static pages.
- Support a simple blog flow:
  - front-end home page
  - content list/details pages
  - admin area
  - OpenList file access integration
- Prefer simple PHP + PDO + JSON style, avoiding unnecessary framework complexity.

## Recommended runtime

- PHP 8.2+
- MySQL or MariaDB (primary)
- SQLite support kept as fallback for local/dev environments
- Apache or Nginx with rewrite rules to `public/index.php`

## Directory layout

- `config/` — application configuration
- `app/` — PHP application classes
- `public/` — web entry point and static assets
- `storage/` — local files or SQLite DB placeholder

## Quick start

1. Copy `.env.example` to `.env` and adjust values.
2. Create the database and import the schema you need.
3. Point your web server root to `web/public`.
4. Visit `/` to confirm the bootstrap works.

## Notes

The project intentionally keeps the core logic plain and explicit so it can be extended gradually without over-engineering.
