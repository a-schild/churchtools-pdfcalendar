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

## Usage
- Go to the `index.php` page with your webbrowser and enter your ct credentials

