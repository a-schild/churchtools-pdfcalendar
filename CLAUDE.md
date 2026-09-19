# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

PHP web application that generates PDF and XLSX calendars from ChurchTools event data. Users log in with ChurchTools credentials, select calendars/resources, and export monthly, yearly or custom date range calendars. The UI is German-language only.

**Requirements:** PHP 8.2+, Composer, web server

**Local toolchain:** PHP and Composer are not on `PATH` on this machine. They ship with
Laragon — prepend `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64` and `C:\laragon\bin\composer`
to `$env:Path` before running `php` or `composer`. (The installed PHP build changes over
time — run `ls C:\laragon\bin\php` before assuming a version.)

`composer update` against the `churchtools-api` VCS repository hits GitHub's anonymous
API rate limit ("Could not authenticate against github.com"). Pass the `gh` token for
the one command: `COMPOSER_AUTH="{\"github-oauth\":{\"github.com\":\"$(gh auth token)\"}}"`.

The 8.3.30 CLI `php.ini` already loads openssl, mbstring, zip, curl, gd and fileinfo
(passing them again only prints "Module already loaded" warnings), so this is enough:

```bash
"C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe" /c/laragon/bin/composer/composer.phar install
```

Older builds had a bare `php.ini` where Composer failed with "openssl extension is
required"; in that case add `-d extension_dir="$P/ext" -d extension=php_openssl.dll …`
for each of the extensions above.

## Build / Install

```bash
cd src
composer install                                  # development
composer install --no-dev --optimize-autoloader    # production
```

There is no test suite, linter, or build step beyond Composer.

## Releases

GitHub Actions (`.github/workflows/build-plugin.yml`) triggers on `v*` tags, runs
`composer install --no-dev` in `src/`, zips the project as
`churchtools-pdfcalendar-<tag>.zip` and publishes a GitHub Release with it.

Release notes are taken from the matching `CHANGELOG.md` section, so **add the entry
before tagging**. The heading must be `## [<version> <date>]` (older entries without a
date, `## [<version>]`, also match); if no section matches the tag, the workflow falls
back to auto-generated notes.

## Architecture

Three-page stateless web flow — no database, no framework, no MVC. All data comes from the ChurchTools REST API on demand. The password is never stored: after login only the ChurchTools session cookies (`$_SESSION["ctCookies"]`) and the server host are kept.

### Page flow

1. **`src/index.php`** — Login form. Collects ChurchTools server URL (or reads it from `config.php`), email, password. POSTs to step 2.
2. **`src/selectcalendars.php`** — Authenticates via ChurchTools API, fetches available calendars/resources/services/tags, renders selection UI with output options (time period, paper size, orientation, filters, colors). Two submit buttons: PDF or XLSX.
3. **`src/generatecalendar.php`** — Restores the ChurchTools session from the saved cookies, fetches appointments for the selected date range, applies public/private and tag filters, generates the chosen output format and streams it as a download.

Shared helpers live in **`src/common.php`** (included by every page): hardened session
start, `ctLogout()` (used by `src/logout.php`), CSRF token, server URL validation,
ChurchTools session save/restore, `h()` escaping, `ctSafeColor()`, `getContrastColor()`,
`ctSetText()` for XLSX cells. `UserInputException` marks errors whose message is safe to
show and which keep the session.

### Security rules

- Escape every value from ChurchTools or the request with `h()` in HTML; colors go
  through `ctSafeColor()`, IDs in inline JS through `(int)`.
- Every POST form carries `csrfToken` (`ctCsrfToken()`), and every POST handler calls
  `ctCheckCsrf()`.
- The server URL comes from `ctResolveServerURL()`: `config.php` wins; user input must
  be a public host name. It installs a `CTClient` without redirects, pinned via
  `CURLOPT_RESOLVE` to the checked IP (against DNS rebinding).
- XLSX text cells use `ctSetText()` (`setCellValue()` turns a leading `=` into a
  formula); only `ctIsWebUrl()` links become hyperlinks.
- `common.php` disables the API client file log (`CTLog::enableFileLog(false)`), which
  would otherwise write into `vendor/` inside the web root. `src/.htaccess` blocks
  `vendor/`, Composer files and `config.php`.
- The API client does *not* round-trip the session cookie via
  `getSessionCookieString()`/`setSessionCookie()`: the string loses the domain of a
  host-only cookie, so the restored cookie is never sent. Save/restore the
  `CookieJar::toArray()` data instead (the jar is reachable as
  `CTConfig::getRequestConfig()["cookies"]`).

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
- Appointments are fetched with `->includeTags()` so tags arrive in the same request.
  Without it, `Appointment::getTags()` lazily fires `/api/tags/appointment/{id}` once
  per appointment (see `Appointment.php`) — an N+1 that a year export would multiply by
  hundreds. Keep `includeTags()` on the request whenever tags are read.
- `PhpSpreadsheet\Shared\Date::PHPToExcel()` reads the DateTime's wall-clock fields in
  its own timezone, and `generatecalendar.php` calls `setTimezone('Europe/Zurich')` first,
  so XLSX times are local. (The TypeScript sibling had a UTC bug here because ExcelJS
  serializes via `getTime()` instead — that bug does not exist in this codebase.)
- Full-year export produces 12 pages (one per month) in PDF, or a single sheet in XLSX.
  The period (`sel_month`: prev/now/next, prev_year/current_year/next_year, range)
  is resolved to `$firstMonth`..`$lastMonth`; a `range` also sets `$rangeStart`/`$rangeEnd`,
  which clip the API from/to of the first and last month (max. `$maxRangeMonths` = 24).
  An invalid range throws `UserInputException`, whose handler keeps the session.
- `index.php` and `selectcalendars.php` use `declare(strict_types=1)`; `generatecalendar.php`
  does *not*, and relies on weak-mode coercion (e.g. it passes `round()`'s float to
  `CalendarBuilder::writeTimestamp()`, which is typed `int`). Adding strict types there
  would break PDF generation without extra casts.
- PHP files mix inline HTML/PHP.
- `CalendarBuilder::output()` discards TCPDF's return value, so only the streaming
  destinations (`I`, `D`) work — `S` returns nothing.
- Frontend uses Bootstrap 5.3.8 (CSS only) and Font Awesome Free 7.3.1 from jsDelivr, with
  SRI hashes. No Bootstrap JS, jQuery or Popper: the page scripts are plain JS. In a
  Bootstrap 5 `.row` every direct child gets `width: 100%`, so wrap inputs and buttons
  in `col-*` divs. Icons use FA 6+ names (`fa-solid fa-right-to-bracket`, not `fa fa-sign-in`).
