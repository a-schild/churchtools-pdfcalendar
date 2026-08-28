# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [1.3.0 2026-08-28]

### Added
- XLSX export has a new "Tags" column between "Adresse" and "Bild", listing all
  tags of an appointment comma-separated
- New option "Tags anzeigen (nur PDF)" appends the tags of an entry in brackets
  to the calendar text, e.g. `Gottesdienst (mit Abendmahl) [Musik, Jugend]`.
  Off by default, so existing PDFs are unchanged.

### Changed
- Appointments are now fetched with `includeTags()`, so tags arrive with the
  single appointments request. Previously `getTags()` lazily issued one extra
  API request per appointment, which also slowed down tag filtering.

## [1.2.10 2026-07-29]

### Fixed
- Repaired the recurring "Dependabot Updates" failures. guzzlehttp/guzzle and
  guzzlehttp/psr7 reached the project only through `5pm-hdh/churchtools-api`, and
  Dependabot security updates are limited to *direct* dependencies, so it could
  never open pull requests for them — which is why those alerts accumulated
  unattended. Both are now declared explicitly in `src/composer.json`; the parent
  already allowed `guzzle ^7`, so no resolved version changed.
- Closed the superseded phpspreadsheet 5.8.1 pull request that each retry was
  failing against.

### Added
- `.github/dependabot.yml` enabling scheduled weekly updates (previously only
  security updates ran) and covering the `github-actions` ecosystem, whose
  pinned major tags in `build-plugin.yml` had never been updated.

This release carries no runtime change: `vendor/` resolves identically to 1.2.9.

## [1.2.9 2026-07-29]

### Security
- Resolved all 19 open Dependabot alerts by upgrading dependencies:
  - phpoffice/phpspreadsheet 5.4.0 → 5.9.0 — fixes SSRF/RCE in `IOFactory::load`
    (critical), XLS/OLE and Gnumeric memory exhaustion, XLSX/SpreadsheetML CPU
    denial of service, `WEBSERVICE()` SSRF redirect bypass, and two XSS issues in
    the HTML writer
  - guzzlehttp/guzzle 7.10.0 → 7.15.2 — fixes URI fragment disclosure in redirect
    `Referer` headers, host-only cookie scope loss, unbounded response cookies,
    cookie disclosure/injection via IP-address domains, `Proxy-Authorization`
    leakage to origin servers, dot-only cookie domain matching, and silent
    HTTPS-proxy downgrade to cleartext
  - guzzlehttp/psr7 2.8.0 → 2.13.0 — fixes host confusion via weak URI host
    validation and authority reinterpretation, plus CRLF injection in start-line
    serialization and the URI host component

### Changed
- Upgraded tcpdf 6.10.1 → 6.11.3, monolog, zipstream-php, composer/pcre and
  symfony/deprecation-contracts to current releases
- Verified PDF and XLSX output is unchanged: regenerating the same calendar
  before and after the upgrade produces byte-identical content streams apart
  from the embedded generation timestamp

## [1.2.8 2026-02-10]

### Changed
- Upgraded pdfcalendarbuilder to 1.0.16
- Reduced release package size by ~80% (~32 MB savings)
  - Optimized favicon files (5.6 MB → 31 KB)
  - Strip unused TCPDF fonts from build (keep only freesans and helvetica)
  - Remove test, doc, example directories and CI configs from vendor in build
  - Exclude non-runtime files (CLAUDE.md, nbproject, composer.lock) from zip

## [1.2.7 2026-02-10]

### Fixed
- Legend overlapping last row of calendar days when more than 7 calendars are selected [#26](https://github.com/a-schild/churchtools-pdfcalendar/issues/26)
- Upgraded pdfcalendarbuilder to 1.0.14 (color rendering fixes, PHP 8.0+ sort compliance)
- Disable PDF/XLSX buttons until at least one calendar is selected [#2](https://github.com/a-schild/churchtools-pdfcalendar/issues/2)

## [1.2.4 2026-01-15]

### Fixed
- Add support for filter by tag
- Upgrade to php 8.2 +

## [1.2.3 2025-09-17]
- Fix github action

## [1.2.2 2025-09-17]

### Fixed
- Upgrade tcpdf dependency to 6.10+ for security fixes

## [1.2.1]

### Added
- Automated build via github actions

## [1.2.0]
### Added
- Upgrade libraries
- Added more export fields in excel export (Image/Address etc.)
- Added option to export without colors, public/private entries

## [1.1.4]

### Fixed
- Fixed the api change in ct for CSRF

## [1.1.3]

### Fixed
- fix for prev/next year on year wrap

## [1.1.2]

### Added
- you can also generate styled xlsx calendars

## [1.1.0]

### Added
- 1.1 you can also generate full-year calendars, consisting of 12 pages
