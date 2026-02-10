# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [1.2.5 2026-02-10]

### Fixed
- Legend overlapping last row of calendar days when more than 7 calendars are selected [#26](https://github.com/a-schild/churchtools-pdfcalendar/issues/26)
- Upgraded pdfcalendarbuilder to 1.0.14 (color rendering fixes, PHP 8.0+ sort compliance)

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
