# churchtools-pdfcalendar
Generate PDF month calendars from churchtools

Currently the UI is in german only

## Requirements
- php 8.0 or better
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

## Usage
- Go to the `index.php` page with your webbrowser and enter your ct credentials


## Changelog
- 1.2.0 Upgrade libraries
        Added more export fields in excel export (Image/Address etc.)
		Added option to export without colors, public/private entries
- 1.1.4 we fixed the api change in ct for CSRF
- 1.1.3 fix for prev/next year on year wrap
- 1.1.2 you can also generate styled xlsx calendars
- 1.1 you can also generate full-year calendars, consisting of 12 pages
