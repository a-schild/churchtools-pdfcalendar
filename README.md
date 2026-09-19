# churchtools-pdfcalendar
Generate PDF month calendars from churchtools

Currently the UI is in german only

## Requirements
- php 8.2 or better
- Churchtools (Tested with v3.101.1)

## Demoserver
- A demo server can be accessed under this URL 
  https://ctdemo.oncloud7.ch/
  You can then specify the name/url of your CT installation, together with valid login credentials

## Installation as complete package
- Download and expand the archive to your web server
  https://github.com/a-schild/churchtools-pdfcalendar/releases
- Copy `config.sample` to `config.php`
- Modify the serverURL to match your churchtool server name

## Installation via console and composer:
- Copy/expand the sources on your webserver
- Copy `config.sample` to `config.php`
- Modify the serverURL to match your churchtool server name
- Run composer to install the required dependencies
  ```
  composer update
  ```

## Security notes
- Serve the application over HTTPS only, the login form sends ChurchTools passwords
- `src/.htaccess` blocks web access to `vendor/`, the Composer files and `config.php`.
  On nginx or other servers without `.htaccess` support, deny these paths in the
  server configuration.
- Without `serverURL` in `config.php`, users may log in to any public ChurchTools
  host. Set it to restrict the installation to your own server.

## Usage
- Go to the `index.php` page with your webbrowser and enter your ct credentials
- Select the calendars and the period to export, then generate a PDF or XLSX file.
  Available periods:
  - previous, current or next month (1 page)
  - previous, current or next year (12 pages)
  - a user defined date range (1 page per month, max. 24 months)

## Changelog
- See [CHANGELOG.md](CHANGELOG.md) for the notable changes of each release

