<?php
require __DIR__.'/vendor/autoload.php';

use \ChurchTools\Api\Tools\CalendarTools;
use \ChurchTools\Api\Tools\BookingTools;


$printLegende = true;

$buildPDF= true;
$buildXLSX= true;
if (isset($_REQUEST['outputFormatXLSX']))
{
    $buildPDF= false;
    $buildXLSX= true;
}

session_start();

$userName= $_SESSION["userName"];
$password= $_SESSION["password"];
$serverURL= $_SESSION["serverURL"];
try
{

    $api = \ChurchTools\Api\RestApi::createWithUsernamePassword($serverURL,
            $userName, $password);

    $calMasterData = $api->getCalendarMasterData();
    $resourceMasterData= $api->getResourceMasterData();
    
    $calendars  = $calMasterData->getCalendars();
    // $visibleResourceTypes= $resourceMasterData->getResourceTypes();
    $visibleResources= $resourceMasterData->getResources();
    
    
    //
    // All calendars
    // 
    $allCalendarIDS= $calendars->getCalendarIDS(true); // Get all calendars sorted
    $outputCalendars= array();
    foreach ($allCalendarIDS as $calID) {
        if (isset($_POST['CAL_'.$calID]))
        {
            array_push($outputCalendars, $calID);
        }
    }

    $allResourceIDS= $visibleResources->getResourceIDS(true); // Get all calendars sorted
    $outputResources= array();
    foreach ($allResourceIDS as $resID) {
        if (isset($_POST['RES_'.$resID]))
        {
            array_push($outputResources, $resID);
        }
    }
    
    if (sizeof($outputCalendars) != 0 || sizeof($outputResources) !=0)
    {
        $paperFormat = "A4";
        if (isset($_POST['sel_paper']))
        {
            $paperFormat= $_POST['sel_paper'];
        }

        $landscape= true;
        if (isset($_POST['orientation']))
        {
            $landscape= $_POST['orientation'] == 'L';
        }

        $printEND= isset($_POST['PrintEND']);
        $printFullYear= false;
        $now = new DateTime();
        $currentDay     = $now->format("d");
        $lastDayOfMonth = $now->format("t");
        $currentMonth   = $now->format("n");
        $currentYear    = $now->format("Y");

        $requestedMonth = new DateTime();
        $requestedYear= $currentYear;
        if (isset($_POST['sel_month']))
        {
            if ($_POST['sel_month'] == 'prev')
            {
                // OK
                $requestedMonth->sub(new DateInterval('P1M'));
            }
            elseif ($_POST['sel_month'] == 'now')
            {
                // OK, NOW already in $requestedMonth
            }
            elseif ($_POST['sel_month'] == 'next')
            {
                // OK
                $requestedMonth->add(new DateInterval('P1M'));
            }
            elseif ($_POST['sel_month'] == 'current_year')
            {
                // OK
                $requestedMonth->setDate($requestedYear, 1, 1);
                $printFullYear= true;
            }
            elseif ($_POST['sel_month'] == 'next_year')
            {
                // OK
                $requestedYear+= 1;
                $requestedMonth->setDate($requestedYear, 1, 1);
                $printFullYear= true;
            }
        }

        // Move to start of day
        $requestedMonth->setTime(0, 0);
        $now->setTime(0, 0);

        $startMonth= 1;
        $endMonth= 12;
        if (!$printFullYear)
        {
            $startMonth= intval($requestedMonth->format("n"));
            $endMonth= intval($requestedMonth->format("n"));
        }

        $cal= null;
        for ($loopMonth= $startMonth; $loopMonth <= $endMonth; $loopMonth++ )
        {
            $requestedMonth->setDate($requestedYear, $loopMonth, 1);
            $rmLastDayOfMonth = $requestedMonth->format("t");
            $rmMonth   = $requestedMonth->format("n");
            $rmYear    = $requestedMonth->format("Y");

            // Calculate start/end dates of requested month
            //$tsStart            = mktime(0, 0, 0, $rmMonth, 1, $rmYear);
            //$startOfMonth  = getDate($tsStart);
            //$days_in_month = date('t', $tsStart);
            //$tsEnd            = mktime(23, 59, 59, $rmMonth, $rmLastDayOfMonth, $rmYear);

            $startDate= DateTime::createFromFormat('Y-m-d', $rmYear . '-' . $rmMonth . '-1');
            $startDate->setTime(0, 0);
            $endDate= DateTime::createFromFormat('Y-m-d', $rmYear . '-' . $rmMonth . '-' . $rmLastDayOfMonth);
            $endDate->setTime(23, 59, 59);

            $dDiffToStart = $startDate->diff($now);
            $dDiffToEnd = $endDate->diff($now);

            $a1= $dDiffToStart->format('%a');
            $a2= $dDiffToEnd->format('%a');

            $numberPreviousDays = (intval($dDiffToStart->format('%a'))+1)*-1;
            $numberNextDays     = intval($dDiffToEnd->format('%a'))+1;

            $calEntries= null;
            $resEntries= null;
            if (sizeof($outputCalendars) > 0)
            {
                // Get calendar entries for month
                $calEntriesUnfiltered = $api->getCalendarEvents($outputCalendars, $numberPreviousDays,
                    $numberNextDays);

                // Filter out entries which are out of date (Due to repeat logic)
                //
                //
                $calUnsortedEntries= CalendarTools::filterCalendarEntries($calEntriesUnfiltered , $startDate->getTimestamp(), $endDate->getTimestamp());

                // Sort array
                $calEntries= CalendarTools::sortCalendarEntries($calUnsortedEntries);
            }
            if (sizeof($outputResources) > 0)
            {
                // Get calendar entries for month
                // $resEntriesUnfiltered
                $resUnfilteredEntries= $api->getResourceBookings();

                // Filter out entries which are out of date (Due to repeat logic)
                $resUnsortedEntries= BookingTools::filterBookingEntries($resUnfilteredEntries , $startDate->getTimestamp(), $endDate->getTimestamp());

                // Sort array
                $resEntries= BookingTools::sortBookingEntries($resUnsortedEntries);
            }

            if (count($outputCalendars) == 1) {
                $thisCal      = $calendars->getCalendar($outputCalendars[0]);
                $caption      = $thisCal->getName();
                $printLegende = false;
            } else {
                $caption      = "Kalender";
                $printLegende = true;
            }
            if ($cal == null)
            {
                $cal = new aschild\PDFCalendarBuilder\CalendarBuilder(intval($requestedMonth->format("m")), intval($requestedMonth->format("Y")),
                    $caption, $landscape, 'mm', $paperFormat);
                /* Customizations */
                $cal->setDayNames(array('Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag',
                    'Freitag', 'Samstag'));
                $cal->setMonthNames(array('Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli',
                    'August', 'September', 'Oktober', 'November', 'Dezember'));
                $cal->setWeekStarts(1); // Europa
                // $cal->setNumberFontSize(25); Für A3
                //$cal->setMargins(5,5,5,5);
                $cal->setResizeRowHeightsIfNeeded(true);
                $cal->setShrinkFontSizeIfNeeded(true);
                $cal->setMargins(5,5,5,5);
                $cal->setPrintEndTime($printEND);
                $cal->startPDF();
            }
            else
            {
                $cal->addMonth(intval($requestedMonth->format("m")), intval($requestedMonth->format("Y")), $caption);
            }

            if ($printLegende) {
                if (sizeof($outputCalendars) > 0)
                {
                    foreach ($outputCalendars as $cid) {
                        $thisCal = $calendars->getCalendar($cid);
                        $cal->addCategory($cid, $thisCal->getName(), $thisCal->getTextColor(),
                            $thisCal->getColor());
                    }
                    $cal->printCategories();
                }
            }

            if ($calEntries != null)
            {
                foreach ($calEntries as $entry) {
                    $calendar  = $calendars->getCalendar($entry->getCalendarID());
                    $startDate = $entry->getStartDate();
                    $endDate = $entry->getEndDate();
                    $title     = $entry->getTitle();
                    $remarks   = $entry->getRemarks();
                    if ($remarks != null && strlen(trim($remarks)) > 0) {
                        $title = $title.' ('.$remarks.')';
                    }
                    $cal->addEntry($startDate, $endDate, $title, $calendar->getTextColor(),
                        $calendar->getColor());
                }
            }

            if ($resEntries != null)
            {
                foreach ($resEntries as $entry) {
                    //var_dump($entry);
                    $resource  = $visibleResources->getResource($entry->getResourceID());
                    $startDate = $entry->getStartDate();
                    $endDate = $entry->getEndDate();
                    $title     = $entry->getTitle();
                    $remarks   = $entry->getRemarks();
                    if ($remarks != null && strlen(trim($remarks)) > 0) {
                        $title = $title.' ('.$remarks.')';
                    }
                    $cal->addEntry($startDate, $endDate, $title);
                }
            }
            
            $cal->buildCalendar();
            $cal->writeTimestamp("@".strftime('%d.%m.%Y %H:%M'), $cal->getPageWidth()-105, 10, 100);
        }
        $cal->Output("calendar-".$requestedMonth->format("Y") .'-'.$requestedMonth->format("m").".pdf", "I");
    }
    else
    { ?>
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
            <h2>Keine Kalender und keine Resource ausgewählt</h2>
            <div class="alert alert-danger" role="alert">
            Nicht's zum generieren gefunden
            </div>
            <div>
                <a href="index.php" class="btn btn-primary">Zum Login</a>
            </div>
        </div>
    </body>
</html>
<?php        
    }
}
catch (Exception $e)
{
    $errorMessage= $e->getMessage();
    $hasError= true;
    session_destroy();
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
            <h2>Fehler</h2>
            <div class="alert alert-danger" role="alert">
            Error: <?= $errorMessage ?>
            </div>
            <div>
                <a href="index.php" class="btn btn-primary">Zum Login</a>
            </div>
        </div>
    </body>
</html>
<?php } ?>

