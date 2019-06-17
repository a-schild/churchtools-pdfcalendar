<?php declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use \ChurchTools\Api\Tools\CalendarTools;

$serverURL= filter_input(INPUT_POST, "serverURL");
$userName= filter_input(INPUT_POST, "email");
$password= filter_input(INPUT_POST, "password");

$hasError= false;
$errorMessage= null;
$visibleCalendars;
try
{
    $api = \ChurchTools\Api\RestApi::createWithUsernamePassword($serverURL,
            $userName, $password);
    $calMasterData= $api->getCalendarMasterData();
    $resourceMasterData= $api->getResourceMasterData();

    $visibleCalendars= $calMasterData->getCalendars();
    $visibleResourceTypes= $resourceMasterData->getResourceTypes();
    $visibleResources= $resourceMasterData->getResources();
    

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
        <title>Churchtools Calendarbuilder</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
        <link rel="stylesheet" href="styles.css">
    </head>
    <body>
        <div class="container">
            <h1>CT Calendarbuilder</h1>
            <?php if ($hasError) { ?>
            <h2>Login fehlgeschlagen</h2>
            <div class="alert alert-danger" role="alert">
            Error in login: <?= $errorMessage ?>
            </div>
            <div>
                <a href="index.php" class="btn btn-primary">Zum Login</a>
            </div>
            <?php } else { ?>
            <h5>Kalender zum generieren anwählen</h5>
            <form action="generatepdf.php" target="_blank" method="post">
                <div class="row">
                    <div class="col-6 calendarcol">
            <?php $calIDS= $visibleCalendars->getCalendarIDS(true);
                    foreach( $calIDS as $calID) {
                        $cal=$visibleCalendars->getCalendar($calID);
                        ?>
                 <div class="calendar form-check" style="background-color: <?= $cal->getColor()?>; color: <?= $cal->getTextColor()?>">
                        <label class="form-check-label" for="CAL_<?= $cal->getID() ?>"><input type="checkbox" class="form-check-input" id="CAL_<?= $cal->getID() ?>" name="CAL_<?= $cal->getID() ?>" value="CAL_<?= $cal->getID() ?>"><?= $cal->getName() ?></label>
                    </div>
            <?php } ?>
                    </div>
                    <div class="col-6 calendarcol">
            <?php $resourceTypesIDS= $visibleResourceTypes->getResourceTypesIDS(true);
                    foreach( $resourceTypesIDS as $resTypeID) {
                        $resType=$visibleResourceTypes->getResourceType($resTypeID);
                        ?>
                    <div class="calendar form-check" >
                        <?= $resType->getDescription() ?><br>
                        <?php 
                            $resourceIDS= $visibleResources->getResourceIDSOfType($resTypeID, true);
                            foreach( $resourceIDS as $resourceID) {
                                $resource=$visibleResources->getResource($resourceID);
                                ?>
                        <?= $resource->getDescription() ?><br/>
                            <?php } ?>
                    </div>
            <?php } ?>
                    </div>
                </div>
                    <h5>Monat / Jahr auswählen</h5>
                    <div class="row">
                        <div class="col">
                            <div class="form-check form-check-inline">
                                <label class="form-check-label"><input type="radio" name="sel_month" value="prev"  class="form-check-input" >Vorangehender Monat</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <label class="form-check-label"><input type="radio" name="sel_month" value="now"  class="form-check-input" checked>Aktueller Monat</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <label class="form-check-label"><input type="radio" name="sel_month" value="next" class="form-check-input">Nächster Monat</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <label class="form-check-label">(1 Seite)</label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-check form-check-inline">
                                <label class="form-check-label"><input type="radio" name="sel_month" value="current_year"  class="form-check-input">Aktuelles Jahr</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <label class="form-check-label"><input type="radio" name="sel_month" value="next_year" class="form-check-input">Nächstes Jahr</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <label class="form-check-label">(12 Seiten)</label>
                            </div>
                        </div>
                    </div>
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
             <div class="form-group row mt-2 ml-1">
                 <input type="submit" value="PDF erstellen" class="btn btn-primary mr-1">
                 <a href="index.php" class="btn btn-secondary mr-1">Abmelden</a>
             </div>
            </form>
            <?php } ?>
        </div>
        <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    </body>
</html>
