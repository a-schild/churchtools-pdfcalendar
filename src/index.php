<?php
declare(strict_types=1); 
session_start();
if (file_exists ( 'config.php' ) )
{
    $configs = include('config.php');
}
else
{
    $configs= null;
}
$serverURL= $configs["serverURL"];
?>
<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>Calendarbuilder login</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" integrity="sha256-eZrrJcwDc/3uDhsdt61sL2oOBY362qM3lon1gyExkL0=" crossorigin="anonymous" />        
		<link rel="icon" type="image/png" href="favicon.png">
    </head>
    <body>
        <div class="container">
        <h1>CT Calendarbuilder</h1>
        <h2>Login mit Ihrem Churchtools Account</h2>
        <form action="selectcalendars.php" method="post">
            <?php if (!isset($serverURL)) { ?>
             <div class="form-group row">
                 <label for="serverURL" class="col-sm-2 col-form-label">Server URL</label>
                <input type="text" name="serverURL" required="required" placeholder="your.church.tools">
             </div>
            <?php } ?>
             <div class="form-group row">
                 <label for="email" class="col-sm-2 col-form-label">E-Mail</label>
                <input type="text" name="email" required="required" placeholder="your churchtool login">
             </div>
             <div class="form-group row">
                 <label for="password" class="col-sm-2 col-form-label">Passwort</label>
            <input type="password" name="password" required="required">
             </div>
             <div class="form-group row">
                 <label for="submit" class="col-sm-2 col-form-label"></label>
                 <button type="submit" class="btn btn-primary">Anmelden <i class="fa fa-sign-in" aria-hidden="true"></i></button>
            <?php if (isset($serverURL)) { ?>
            <input type="hidden" name="serverURL" value="<?= $serverURL ?>" >
            <?php } ?>
             </div>
        </form>
        </div>
        <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    </body>
</html>
