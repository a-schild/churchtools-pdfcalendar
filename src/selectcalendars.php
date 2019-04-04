<?php declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use \ChurchTools\Api\Tools\CalendarTools;

$serverURL= filter_input(INPUT_POST, "serverURL");
$userName= filter_input(INPUT_POST, "email");
$password= filter_input(INPUT_POST, "password");

$api = \ChurchTools\Api\RestApi::createWithUsernamePassword($serverURL,
        $userName, $password);

$hasError= false;
$errorMessage= null;
$visibleCalendars;
try
{
    $calMasterData= $api->getCalendarMasterData();

    $visibleCalendars= $calMasterData->getCalendars();
    session_start();
    $_SESSION['userName'] = $userName;
    $_SESSION['password'] = $password;
    $_SESSION['serverURL']= $serverURL;
}
catch (Exception $e)
{
    $errorMessage= $e->getMessage();
    $hasError= true;
    session_destroy();
}
?>
<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>Calendarbuilder login</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    </head>
    <body>
        <div class="container">
            <h1>CT Calendarbuilder</h1>
            <?php if ($hasError) { ?>
            <h2>Login fehlgeschlagen</h2>
            <div class="alert alert-danger" role="alert">
            Error in login <?= $errorMessage ?>
            </div>
            <div>
                <a href="index.php" class="btn btn-primary">Zum Login</a>
            </div>
            <?php } else { ?>
            <h5>Kalender zum generieren anwählen</h5>
            <form action="generatepdf.php" target="_blank" method="post">
            <?php $calIDS= $visibleCalendars->getCalendarIDS(true);
                    foreach( $calIDS as $calID) {
                        $cal=$visibleCalendars->getCalendar($calID);
                        ?>
                 <div class="form-check" style="background-color: <?= $cal->getColor()?>; color: <?= $cal->getTextColor()?>">
                        <label class="form-check-label" for="CAL_<?= $cal->getID() ?>"><input type="checkbox" class="form-check-input" id="CAL_<?= $cal->getID() ?>" name="CAL_<?= $cal->getID() ?>" value="CAL_<?= $cal->getID() ?>"><?= $cal->getName() ?></label>
                    </div>
            <?php } ?>
                    <h5>Monat auswählen</h5>
<!--                <div class="form-check form-check-inline">
                    <label class="form-check-label"><input type="radio" name="sel_month" value="prev"  class="form-check-input" >Vorangehender Monat</label>
                </div>-->
                <div class="form-check form-check-inline">
                    <label class="form-check-label"><input type="radio" name="sel_month" value="now"  class="form-check-input" checked>Aktueller Monat</label>
                </div>
<!--                <div class="form-check form-check-inline">
                    <label class="form-check-label"><input type="radio" name="sel_month" value="next" class="form-check-input">Nächster Monat</label>
                </div>-->
                    <h5>Papierformat</h5>
                <div class="form-check form-check-inline">
                    <label class="form-check-label"><input type="radio" name="sel_paper" value="A5"  class="form-check-input">A5</label>
                </div>
                <div class="form-check form-check-inline">
                    <label class="form-check-label"><input type="radio" name="sel_paper" value="A4"  class="form-check-input" checked>A4</label>
                </div>
                <div class="form-check form-check-inline">
                    <label class="form-check-label"><input type="radio" name="sel_paper" value="A3"  class="form-check-input">A3</label>
                </div>
                <div class="form-check form-check-inline">
                    <label class="form-check-label"><input type="radio" name="sel_paper" value="A2"  class="form-check-input">A2</label>
                </div>
                <h5>Ausrichtung</h5>
                <div class="form-check form-check-inline">
                    <label class="form-check-label"><input type="radio" name="orientation" value="L"  class="form-check-input">Querformat</label>
                </div>
                <div class="form-check form-check-inline">
                    <label class="form-check-label"><input type="radio" name="orientation" value="P" checked class="form-check-input">Hochformat</label>
                </div>
                <div class="form-check form-check-inline">
                        <input type="checkbox" class="form-check-input" id="PrintEND" name="PrintEND" value="PrintEND">
                        <label class="form-check-label" for="PrintEND">Endzeit anzeigen</label>
                </div>
             <div class="form-group row">
                 <input type="submit" value="PDF erstellen" class="btn btn-primary">
             </div>
            </form>
            <hr />
            <div class="row"><a href="index.php" class="btn btn-secondary">Abmelden</a></div>
            <?php } ?>
        </div>
        <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    </body>
</html>
