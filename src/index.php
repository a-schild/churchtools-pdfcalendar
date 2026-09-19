<?php
declare(strict_types=1); 
require __DIR__.'/vendor/autoload.php';
require __DIR__.'/common.php';

ctStartSession();
$serverURL= ctConfiguredServerURL();
?>
<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>Calendarbuilder login</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@7.3.1/css/all.min.css" integrity="sha384-qrALq7+6jBOZIQsNnT6xGkMDru64qD6uTlDra39xrt2SoXl4pO3FX6Roz/RpR/BS" crossorigin="anonymous">
		<link rel="icon" type="image/png" href="favicon.png">
    </head>
    <body>
        <div class="container">
        <h1>CT Calendarbuilder</h1>
        <h2>Login mit Ihrem Churchtools Account</h2>
        <form action="selectcalendars.php" method="post">
            <?php if (!isset($serverURL)) { ?>
             <div class="mb-3 row">
                 <label for="serverURL" class="col-sm-2 col-form-label">Server URL</label>
                 <div class="col-sm-6">
                     <input type="text" class="form-control" id="serverURL" name="serverURL" required="required" placeholder="your.church.tools">
                 </div>
             </div>
            <?php } ?>
             <div class="mb-3 row">
                 <label for="email" class="col-sm-2 col-form-label">E-Mail</label>
                 <div class="col-sm-6">
                     <input type="text" class="form-control" id="email" name="email" required="required" placeholder="your churchtool login" autocomplete="username">
                 </div>
             </div>
             <div class="mb-3 row">
                 <label for="password" class="col-sm-2 col-form-label">Passwort</label>
                 <div class="col-sm-6">
                     <input type="password" class="form-control" id="password" name="password" required="required" autocomplete="current-password">
                 </div>
             </div>
             <div class="mb-3 row">
                 <div class="col-sm-6 offset-sm-2">
                     <button type="submit" class="btn btn-primary">Anmelden <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i></button>
                     <input type="hidden" name="csrfToken" value="<?= h(ctCsrfToken()) ?>">
                 </div>
             </div>
        </form>
        </div>
    </body>
</html>
