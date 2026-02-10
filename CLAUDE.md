# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

PHP web application that generates PDF and XLSX calendars from ChurchTools event data. Users log in with ChurchTools credentials, select calendars/resources, and export monthly or yearly calendars. The UI is German-language only.

**Requirements:** PHP 8.2+, Composer, web server

## Build / Install

```bash
cd src
composer install                                  # development
composer install --no-dev --optimize-autoloader    # production
```

There is no test suite, linter, or build step beyond Composer.

## Releases

GitHub Actions (`.github/workflows/build-plugin.yml`) triggers on `v*` tags, runs `composer install --no-dev` in `src/`, zips the project, and uploads to GitHub Releases.

## Architecture

Three-page stateless web flow — no database, no framework, no MVC. All data comes from the ChurchTools REST API on demand; credentials live only in `$_SESSION`.

### Page flow

1. **`src/index.php`** — Login form. Collects ChurchTools server URL (or reads it from `config.php`), email, password. POSTs to step 2.
2. **`src/selectcalendars.php`** — Authenticates via ChurchTools API, fetches available calendars/resources/services/tags, renders selection UI with output options (time period, paper size, orientation, filters, colors). Two submit buttons: PDF or XLSX.
3. **`src/generatecalendar.php`** — Re-authenticates, fetches appointments for the selected date range, applies public/private and tag filters, generates the chosen output format and streams it as a download.

### Key dependencies

| Package | Purpose |
|---------|---------|
| `5pm-hdh/churchtools-api` | ChurchTools REST API client (custom branch: `dev-appointment-tag-support`) |
| `a-schild/pdfcalendarbuilder` | PDF calendar grid generation (wraps TCPDF) |
| `phpoffice/phpspreadsheet` | XLSX generation |

### Configuration

Copy `src/config.sample` to `src/config.php`. The only setting is `serverURL` — if set, the login page hides the server URL field.

### Notable implementation details

- Timezone is hardcoded to `Europe/Zurich`.
- Color contrast for text on colored backgrounds is computed via `getContrastColor()` in `generatecalendar.php`.
- Tag filtering uses OR logic (entries matching ANY selected tag are included).
- Full-year export produces 12 pages (one per month) in PDF, or a single sheet in XLSX.
- PHP files use `declare(strict_types=1)` and inline HTML/PHP mixing.
- Frontend uses Bootstrap 4.3.1, Font Awesome 4.7.0, jQuery 3.3.1 (all CDN).
