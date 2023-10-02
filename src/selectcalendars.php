<?php declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use \CTApi\CTConfig;
use \CTApi\Models\Common\Config\ConfigRequest;
use \CTApi\Models\Calendars\Calendar\CalendarRequest;
use \CTApi\Models\Calendars\Resource\ResourceRequest;
use \CTApi\Models\Events\Service\ServiceRequest;

$serverURL= filter_input(INPUT_POST, "serverURL");
$userName= filter_input(INPUT_POST, "email");
$password= filter_input(INPUT_POST, "password");

$hasError= false;
$errorMessage= null;
$visibleCalendars;
try
{
    CTConfig::setApiUrl('https://'.$serverURL);
    //authenticates the application and load the api-key into the config
    CTConfig::authWithCredentials(
        $userName,
        $password
    );
    $configData= ConfigRequest::getConfig();
    $visibleCalendars = CalendarRequest::all();
    
//    var_dump($calMasterData->getUnparsedDataBlocks());
    $allResources= ResourceRequest::all();
    $allResourceTypes= [];
    foreach ($allResources as $resource) {
        $rtFound= false;
        foreach ($allResourceTypes as $rType) {
            if ($rType->getId() == $resource->getResourceTypeId()) {
                $rtFound= true;
            }
        }
        if (!$rtFound) {
            array_push($allResourceTypes, $resource->getResourceType());
        }
    }
//    var_dump($resourceMasterData);
//    var_dump($resourceMasterData->getUnparsedDataBlocks());
    $serviceMasterData= ServiceRequest::all();
//    var_dump($serviceMasterData->getUnparsedDataBlocks());

//    $personMasterData= $api->getPersonMasterData();
//    var_dump($personMasterData->getUnparsedDataBlocks());
//    $groupTypes= $personMasterData->getGroupTypes();
//    var_dump($groupTypes);
//    
//    $groups= $personMasterData->getGroups();
//    var_dump($groups);

//    $groupMeetings= $api->getGroupMeetings(138);
//    var_dump($groupMeetings);
    
//    $visibleCalendars= $calMasterData->getCalendars();


//    $visibleResourceTypes= $resourceMasterData->getResourceTypes();
//    $visibleResources= $resourceMasterData->getResources();
//    $visibleServiceGroups= $serviceMasterData->getServiceGroups();
//    $visibleServices= $serviceMasterData->getServiceEntries();
 
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
            function toggleSrvGrpCat(idToToggle)
            {
                    //var divTitle= document.getElementById("ID_"+idToToggle+"_TITLE");
                    var divPlus= document.getElementById("SRVGRP_"+idToToggle+"_PLUS");
                    var divContent= document.getElementById("SRVGRP_WRAPPER_"+idToToggle);
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
            function toggleSrvGrp(srvGrpIdToToggle)
            {
                var headerCB= document.getElementById("SRVGRP_"+srvGrpIdToToggle);
                var isChecked= headerCB.checked;
                var restCBS= document.getElementsByClassName("SRV_"+srvGrpIdToToggle);
                Array.prototype.forEach.call(restCBS, function(el) {
                    // Do stuff here
                    el.checked= isChecked;
                });
            }
            </script>
    </head>
    <body>
        <div class="container">
            <h1>Churchtools Calendarbuilder</h1>
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
                    <div class="col-4 calendarcol">
                        <h5><?= $configData['churchcal_name'] ?></h5>
            <?php   $gcalHeaderWritten= false;
                    foreach( $visibleCalendars as $cal) { 
                        if (!$gcalHeaderWritten && !$cal->getIsPublic() ) {
                            $gcalHeaderWritten= true;
                            ?><h6>Gruppenkalender</h6><?php
                        }
                        ?>
                    <div class="calendar form-check" style="background-color: <?= $cal->getColor()?>; color:<?= getContrastColor($cal->getColor())?>;">
                        <label class="form-check-label" for="CAL_<?= $cal->getId() ?>"><input type="checkbox" class="form-check-input" id="CAL_<?= $cal->getId() ?>" name="CAL_<?= $cal->getId() ?>" value="CAL_<?= $cal->getId() ?>"><?= $cal->getName() ?></label>
                    </div>
            <?php } ?>
                    </div>
                    <div class="col-4 resourcecol">
                        <h5>Resourcen</h5>
            <?php foreach( $allResourceTypes as $resType) {
                        ?>
                    <div class="resource form-check"  >
                        <div class="resourcetype">
                            <input type="checkbox" class="form-check-input" id="REST_<?= $resType->getId()?>" onclick="toggleResType('<?= $resType->getId()?>')"/>
                            <a href="#"  onclick="toggleResTypeCat('<?= $resType->getId() ?>'); return false;">
                                <h6 class="col-10"><?= $resType->getName() ?></h6>
                                    <i class="col-1 fa fa-plus-square-o" aria-hidden="true" id="REST_<?= $resType->getId()?>_PLUS"></i>
                            </a>
                      <?php foreach ($allResources as $resource) {
                                // Check if in resource type
                                if ($resource->getResourceTypeId() == $resType->getId()) {
                                ?>
                            &nbsp;<label class="form-check-label" for="RES_<?= $resource->getId() ?>">
                                <input type="checkbox" class="form-check-input RES_<?= $resType->getId()?>" id="RES_<?= $resource->getId() ?>" name="RES_<?= $resource->getId() ?>" value="RES_<?= $resource->getId() ?>">
                                        <?= $resource->getName() ?></label><br/>
                            <?php } ?>
                        <?php }  ?>
                        </div>
                    </div>
            <?php } ?>
                </div>
                </div>
                    <h5>Zeitraum auswählen</h5>
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
                <h5>Filter</h5>
                <div class="form-check form-check-inline">
                    <label class="form-check-label"><input type="radio" name="show_private" value="private"  class="form-check-input">Nur private</label>
                </div>
                <div class="form-check form-check-inline">
                    <label class="form-check-label"><input type="radio" name="show_private" value="public" checked class="form-check-input">Nur öffentliche</label>
                </div>
                <div class="form-check form-check-inline">
                    <label class="form-check-label"><input type="radio" name="show_private" value="all" class="form-check-input">Alle</label>
                </div>
                <h5>Optionen</h5>
                <div class="form-check form-check-inline">
                        <input type="checkbox" class="form-check-input" id="PrintEND" name="PrintEND" value="PrintEND">
                        <label class="form-check-label" for="PrintEND">Endzeit anzeigen</label>
                </div>
                <div class="form-check form-check-inline">
                        <input type="checkbox" class="form-check-input" id="useColors" name="useColors" value="useColors" checked>
                        <label class="form-check-label" for="useColors">Farben verwenden</label>
                </div>
             <div class="form-group row mt-2 ml-1">
                 <button type="submit" name="outputFormatPDF" value="PDF erstellen" class="btn btn-primary mr-1">PDF erstellen <i class="fa fa-file-pdf-o" aria-hidden="true"></i></button>
                 <button type="submit" name="outputFormatXLSX" value="XLSX erstellen" class="btn btn-primary mr-1">XLSX erstellen <i class="fa fa-file-excel-o" aria-hidden="true"></i></button>
                 <a href="index.php" class="btn btn-secondary mr-1">Abmelden <i class="fa fa-sign-out" aria-hidden="true"></i></a>
             </div>
            </form>
            <?php } ?>
        </div>
        <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    </body>
</html>
<?php 

function getContrastColor($hexcolor) 
{               
    $r = hexdec(substr($hexcolor, 1, 2));
    $g = hexdec(substr($hexcolor, 3, 2));
    $b = hexdec(substr($hexcolor, 5, 2));
    $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
    return ($yiq >= 128) ? 'black' : 'white';
}

