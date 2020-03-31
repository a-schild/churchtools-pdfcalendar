# churchtools-pdfcalendar
Generate PDF month calendars from churchtools
- With version 1.1 you can also generate full-year calendars, consisting of 12 pages
- With version 1.1.2 you can also generate styled xlsx calendars
- With version 1.1.3 fix for prev/next year on year wrap
- With version 1.1.4 we fixed the api change in ct for CSRF

Currently the UI is in german only

## Requirements
- php 7.1 or better
- Churchtools (Tested with 3.52.0)

## Demoserver
- A demo server can be accessed under this URL 
  https://ctdemo.oncloud7.ch/
  You can then specify the name/url of your CT installation, together with valid login credentials

## Installation as complete package
- Download and expand the archive to your web server
  https://github.com/a-schild/churchtools-pdfcalendar/releases/download/1.1.3/churchtools-pdfcalendar-1.1.3.zip
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
