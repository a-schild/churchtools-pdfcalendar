<?php
require __DIR__.'/vendor/autoload.php';

use \ChurchTools\Api\Tools\CalendarTools;


$printLegende = true;


session_start();

$userName= $_SESSION["userName"];
$password= $_SESSION["password"];
$serverURL= $_SESSION["serverURL"];

$api = \ChurchTools\Api\RestApi::createWithUsernamePassword($serverURL,
        $userName, $password);

$calMasterData = $api->getCalendarMasterData();
$calendars  = $calMasterData->getCalendars();
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

$now = new DateTime();
$currentDay     = $now->format("d");
$lastDayOfMonth = $now->format("t");
$currentMonth   = $now->format("n");
$currentYear    = $now->format("Y");

$requestedMonth = new DateTime();
if (isset($_POST['sel_month']))
{
    if ($_POST['sel_month'] == 'prev')
    {
        // OK
        $requestedMonth->sub(new DateInterval('P1M'));
    }
    else if ($_POST['sel_month'] == 'now')
    {
        // OK, NOW already in $requestedMonth
    }
    else if ($_POST['sel_month'] == 'next')
    {
        // OK
        $requestedMonth->add(new DateInterval('P1M'));
    }
}

// Move to start of day
$requestedMonth->setTime(0, 0);
$now->setTime(0, 0);

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

// Get calendar entries for month
$entriesUnfiltered = $api->getCalendarEvents($outputCalendars, $numberPreviousDays,
    $numberNextDays);


// Filter out entries which are out of date (Due to repeat logic)
//
//
$unsortedEntries= CalendarTools::filterCalendarentries($entriesUnfiltered, $startDate->getTimestamp(), $endDate->getTimestamp());


// Sort array
$entries= CalendarTools::sortCalendarentries($unsortedEntries);

if (count($outputCalendars) == 1) {
    $thisCal      = $calendars->getCalendar($outputCalendars[0]);
    $caption      = $thisCal->getName();
    $printLegende = false;
} else {
    $caption      = "Kalender";
    $printLegende = true;
}
$cal = new aschild\PDFCalendarBuilder\CalendarBuilder(intval($now->format("m")), intval($now->format("y")),
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

if ($printLegende) {
    foreach ($outputCalendars as $cid) {
        $thisCal = $calendars->getCalendar($cid);
        $cal->addCategory($cid, $thisCal->getName(), $thisCal->getTextColor(),
            $thisCal->getColor());
    }
    $cal->printCategories();
}

foreach ($entries as $entry) {
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

$cal->buildCalendar();
$cal->writeTimestamp("Stand ".strftime('%d.%m.%Y %H:%M'), $cal->getPageWidth()-105, 10, 100);
$cal->Output("calendar.pdf", "I");

