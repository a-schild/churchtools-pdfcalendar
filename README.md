# churchtools-pdfcalendar
Generate PDF month calendars from churchtools

Currently the UI is in german only

## Requirements
- php 7.3 or better
- Churchtools (Tested with 3.43.1)


## Installation as compplete package
- Download and expand the archive to your web server
  https://github.com/a-schild/churchtools-pdfcalendar/releases/download/1.0/churchtools-pdfcalendar-1.0.zip
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
