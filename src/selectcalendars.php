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
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" integrity="sha256-eZrrJcwDc/3uDhsdt61sL2oOBY362qM3lon1gyExkL0=" crossorigin="anonymous" />        
        <link rel="stylesheet" href="styles.css">
        <script>
            function toggleResTypeCat(idToToggle)
            {
                    //var divTitle= document.getElementById("ID_"+idToToggle+"_TITLE");
                    var divPlus= document.getElementById("REST_"+idToToggle+"_PLUS");
                    var divContent= document.getElementById("REST_WRAPPER_"+idToToggle);
                    if (divContent.style.display === "none" || divContent.style.display === "" )
                    {
                            divPlus.classList.add("fa-minus-square");
                            divPlus.classList.remove("fa-plus-square");
                            divContent.style.display= "block";
                    }
                    else
                    {
                            divPlus.classList.remove("fa-minus-square");
                            divPlus.classList.add("fa-plus-square");
                            divContent.style.display = "none";
                    }
            }
            function toggleResType(resTypeIdToToggle)
            {
                var headerCB= document.getElementById("REST_"+resTypeIdToToggle);
                var isChecked= headerCB.checked;
                var restCBS= document.getElementsByClassName("RES_"+resTypeIdToToggle);
                Array.prototype.forEach.call(restCBS, function(el) {
                    // Do stuff here
                    el.checked= isChecked;
                });
            }
            </script>
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
            <form action="generatecalendar.php" target="_blank" method="post">
                <div class="row">
                    <div class="col-6 calendarcol">
                        <h5>Kalender</h5>
            <?php $calIDS= $visibleCalendars->getCalendarIDS(true);
                    foreach( $calIDS as $calID) {
                        $cal=$visibleCalendars->getCalendar($calID);
                        ?>
                 <div class="calendar form-check" style="background-color: <?= $cal->getColor()?>; color: <?= $cal->getTextColor()?>">
                        <label class="form-check-label" for="CAL_<?= $cal->getID() ?>"><input type="checkbox" class="form-check-input" id="CAL_<?= $cal->getID() ?>" name="CAL_<?= $cal->getID() ?>" value="CAL_<?= $cal->getID() ?>"><?= $cal->getName() ?></label>
                    </div>
            <?php } ?>
                    </div>
                    <div class="col-6 resourcecol">
                        <h5>Resourcen</h5>
            <?php $resourceTypesIDS= $visibleResourceTypes->getResourceTypesIDS(true);
                    foreach( $resourceTypesIDS as $resTypeID) {
                        $resType=$visibleResourceTypes->getResourceType($resTypeID);
                        $resourceIDS= $visibleResources->getResourceIDSOfType($resTypeID, true);
                        if (sizeof($resourceIDS) >0) { // Hide empty types
                        ?>
                    <div class="resource form-check"  >
                        <div class="resourcetype">
                            <input type="checkbox" class="form-check-input" id="REST_<?= $resType->getID()?>" onclick="toggleResType('<?= $resType->getID()?>')"/>
                            <a href="#"  onclick="toggleResTypeCat('<?= $resType->getID() ?>'); return false;">
                                <h6 class="col-10"><?= $resType->getDescription() ?></h6>
                                    <i class="col-1 fa fa-plus-square-o" aria-hidden="true" id="REST_<?= $resType->getID()?>_PLUS"></i>
                            </a>
                        </div>
                        <div id="REST_WRAPPER_<?= $resType->getID()?>" style="display: none;" class="rest-wrapper">
                        <?php 
                            foreach( $resourceIDS as $resourceID) {
                                $resource=$visibleResources->getResource($resourceID);
                                ?>
                            &nbsp;<label class="form-check-label" for="RES_<?= $resource->getID() ?>">
                                <input type="checkbox" class="form-check-input RES_<?= $resType->getID()?>" id="RES_<?= $resource->getID() ?>" name="RES_<?= $resource->getID() ?>" value="RES_<?= $resource->getID() ?>">
                                        <?= $resource->getDescription() ?></label><br/>
                            <?php } ?>
                        </div>
                    </div>
                    <?php } } ?>
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
                 <input type="submit" name="outputFormatPDF" value="PDF erstellen" class="btn btn-primary mr-1">
                 <input type="submit" name="outputFormatXLSX" value="XLSX erstellen" class="btn btn-primary mr-1">
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
