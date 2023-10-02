<?php
require __DIR__.'/vendor/autoload.php';

use \CTApi\CTConfig;
use \CTApi\Models\Calendars\Calendar\CalendarRequest;
use \CTApi\Models\Calendars\Resource\ResourceRequest;
use \CTApi\Models\Events\Service\ServiceRequest;
use \CTApi\Models\Calendars\Appointment\AppointmentRequest;
use \CTApi\Utils\CTDateTimeService;
use \PhpOffice\PhpSpreadsheet\Spreadsheet;
use \PhpOffice\PhpSpreadsheet\Writer\Xlsx;


$printLegende = true;
$excelTitleFontSize= 20;        // Font size of title line
$excelHeaderBGColor= 'dddddd';  // Background color of header line
$excelHeaderFontSize= 12;       // Font size of header line
$excelEvenBGColor= 'eeeeee';    // Background color of even month lines (Only with full year)
$excelDateColWidth= 15;

$buildPDF= true;
$buildXLSX= true;
if (isset($_REQUEST['outputFormatXLSX']))
{
    $buildPDF= false;
    $buildXLSX= true;
}


function cellColor($sheet,$cells,$color){
    $sheet->getStyle($cells)
    ->getFill()
    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
    ->getStartColor()
    ->setRGB($color); //i.e,colorcode=D3D3D3;
}

session_start();

$userName= $_SESSION["userName"];
$password= $_SESSION["password"];
$serverURL= $_SESSION["serverURL"];
try
{

    CTConfig::setApiUrl('https://'.$serverURL);
    //authenticates the application and load the api-key into the config
    CTConfig::authWithCredentials(
        $userName,
        $password
    );
    
    //
    // All calendars
    // 
    $visibleCalendars = CalendarRequest::all();
    $outputCalendars= array();
    $outputCalendarsIDS= array();
    foreach ($visibleCalendars as $calendar) {
        if (isset($_POST['CAL_'.$calendar->getId()]))
        {
            array_push($outputCalendars, $calendar);
            array_push($outputCalendarsIDS, $calendar->getId());
        }
    }

    $showPrivate= false;
    $showPublic= true;
    $requiredValue= $_POST["show_private"];
    if ($requiredValue == "all") {
        $showPrivate= true;
        $showPublic= true;
    } else if ($requiredValue == "private") {
        $showPrivate= true;
        $showPublic= false;
    } else {
        $showPrivate= false;
        $showPublic= true;
    }
    
    if (sizeof($outputCalendars) != 0 )
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
        $useColors= isset($_POST['useColors']);
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
                $requestedYear= intval($requestedMonth->format("Y"));
            }
            elseif ($_POST['sel_month'] == 'now')
            {
                // OK, NOW already in $requestedMonth
            }
            elseif ($_POST['sel_month'] == 'next')
            {
                // OK
                $requestedMonth->add(new DateInterval('P1M'));
                $requestedYear= intval($requestedMonth->format("Y"));
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
        $sheet= null;
        $rowPos= 1;
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
                // Make sure to have a valid expression, and not something like "now - -1"
                 if ($numberPreviousDays < 0) {
                     $fromDate= Date('Y-m-d', strtotime('+'.($numberPreviousDays*-1).' days'));
                 } else {
                     $fromDate= Date('Y-m-d', strtotime('-'.$numberPreviousDays.' days'));
                 }
                 // Make sure to have a valid expression, and not something like "now - -1"
                 if ($numberNextDays < 0) {
                     $toDate= Date('Y-m-d', strtotime('-'.($numberNextDays*-1).' days'));
                 } else {
                     $toDate= Date('Y-m-d', strtotime('+'.$numberNextDays.' days'));
                 }
                
                $calEntries= AppointmentRequest::forCalendars($outputCalendarsIDS)->where('from', $fromDate)
                    ->where('to', $toDate)
                    ->get();

                $calEntries= filterPublicPrivate($calEntries, $showPublic, $showPrivate);
//                $calEntriesUnfiltered = $api->getCalendarEvents($outputCalendars, $numberPreviousDays,
//                    $numberNextDays);

                // Filter out entries which are out of date (Due to repeat logic)
                //
                //
//                $calUnsortedEntries= CalendarTools::filterCalendarEntries($calEntriesUnfiltered , $startDate->getTimestamp(), $endDate->getTimestamp());

                // Sort array
//                $calEntries= CalendarTools::sortCalendarEntries($calUnsortedEntries);
            }
//            if (sizeof($outputResources) > 0)
//            {
//                // Get calendar entries for month
//                // $resEntriesUnfiltered
//                $resUnfilteredEntries= $api->getResourceBookings();
//
//                // Filter out entries which are out of date (Due to repeat logic)
//                $resUnsortedEntries= BookingTools::filterBookingEntries($resUnfilteredEntries , $startDate->getTimestamp(), $endDate->getTimestamp());
//
//                // Sort array
//                $resEntries= BookingTools::sortBookingEntries($resUnsortedEntries);
//            }

            if (count($outputCalendars) == 1) {
                $thisCal      = $outputCalendars[0];
                $caption      = $thisCal->getName();
                $printLegende = false;
            } else {
                $caption      = "Kalender";
                $printLegende = true;
            }
            if ($cal == null)
            {
                if ($buildPDF)
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
                    // Make XLSX
                    $cal = new Spreadsheet();
                    $sheet = $cal->getActiveSheet();
                    if ($paperFormat == 'A5')
                    {
                        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A5);
                    }
                    else if ($paperFormat == 'A4')
                    {
                        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
                    }
                    else if ($paperFormat == 'A3')
                    {
                        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A3);
                    }
                    else if ($paperFormat == 'A2')
                    {
                        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A2_PAPER);
                    }
                    else
                    {
                        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
                    }
                    if ($landscape)
                    {
                        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
                        $sheet->getPageMargins()->setTop(0.25);
                        $sheet->getPageMargins()->setRight(0.5);
                        $sheet->getPageMargins()->setLeft(0.5);
                        $sheet->getPageMargins()->setBottom(0.25);       
                    }
                    else
                    {
                        $sheet->getPageMargins()->setTop(0.5);
                        $sheet->getPageMargins()->setRight(0.25);
                        $sheet->getPageMargins()->setLeft(0.25);
                        $sheet->getPageMargins()->setBottom(0.5);       
                    }
                    //$sheet->getPageSetup()->setFitToWidth(1);  // Scale to 1 page width
                    //$sheet->getPageSetup()->setFitToHeight(0); // Don't scale to height
                    $sheet->getStyle( 'A'.$rowPos )->getFont()->setBold( true )->setSize($excelTitleFontSize);
                    $sheet->setCellValue('A'.$rowPos++, $caption);
                    $myCol= 'A';
                    if ($printLegende)
                    {
                        $sheet->getStyle( $myCol.$rowPos )->getFont()->setBold( true )->setSize($excelHeaderFontSize);
                        $sheet->getColumnDimension($myCol)->setAutoSize(true);
                        cellColor($sheet, $myCol.$rowPos, $excelHeaderBGColor);
                        $sheet->setCellValue($myCol.$rowPos, 'Kalender');
                        $myCol++;
                    }
                    $sheet->getStyle( $myCol.$rowPos )->getFont()->setBold( true )->setSize($excelHeaderFontSize);
                    $sheet->getColumnDimension($myCol)->setWidth($excelDateColWidth);
                    cellColor($sheet, $myCol.$rowPos, $excelHeaderBGColor);
                    $sheet->setCellValue($myCol++.$rowPos, 'Start');
                    if ($printEND)
                    {
                        $sheet->getStyle( $myCol.$rowPos )->getFont()->setBold( true )->setSize($excelHeaderFontSize);
                        $sheet->getColumnDimension($myCol)->setWidth($excelDateColWidth);
                        cellColor($sheet, $myCol.$rowPos, $excelHeaderBGColor);
                        $sheet->setCellValue($myCol++.$rowPos, 'Ende');
                    }
                    $sheet->getStyle( $myCol.$rowPos )->getFont()->setBold( true )->setSize($excelHeaderFontSize);
                    $sheet->getColumnDimension($myCol)->setAutoSize(true);
                    cellColor($sheet, $myCol.$rowPos, $excelHeaderBGColor);
                    $sheet->setCellValue($myCol++.$rowPos, 'Titel');
                    $sheet->getStyle( $myCol.$rowPos )->getFont()->setBold( true )->setSize($excelHeaderFontSize);
                    //$sheet->getColumnDimension($myCol)->setAutoSize(true);
                    cellColor($sheet, $myCol.$rowPos, $excelHeaderBGColor);
                    $sheet->setCellValue($myCol++.$rowPos, 'Bemerkung');
                    $sheet->getStyle( $myCol.$rowPos )->getFont()->setBold( true )->setSize($excelHeaderFontSize);
                    //$sheet->getColumnDimension($myCol)->setAutoSize(true);
                    cellColor($sheet, $myCol.$rowPos, $excelHeaderBGColor);
                    $sheet->setCellValue($myCol++.$rowPos, 'Weitere infos');
                    $sheet->getStyle( $myCol.$rowPos )->getFont()->setBold( true )->setSize($excelHeaderFontSize);
                    $sheet->getColumnDimension($myCol)->setAutoSize(true);
                    cellColor($sheet, $myCol.$rowPos, $excelHeaderBGColor);
                    $sheet->setCellValue($myCol++.$rowPos, 'Link');
                    $rowPos++;
                }
            }
            else
            {
                if ($buildPDF)
                {
                    $cal->addMonth(intval($requestedMonth->format("m")), intval($requestedMonth->format("Y")), $caption);
                }
            }

            if ($printLegende && $buildPDF) {
                if (sizeof($outputCalendars) > 0)
                {
                    foreach ($outputCalendars as $thisCal) {
                        if ($useColors) {
                            $cal->addCategory($thisCal->getId(), $thisCal->getName(), getContrastColor($thisCal->getColor()),
                                $thisCal->getColor());
                        } else {
                            $cal->addCategory($thisCal->getId(), $thisCal->getName(), '#ffffff',
                                '#000000');
                        }
                    }
                    $cal->printCategories();
                }
            }

            if ($calEntries != null)
            {
                foreach ($calEntries as $entry) {
                    $calendar  = $entry->getCalendar();
                    $startDate = new DateTime($entry->getStartDate());
                    $endDate = new DateTime($entry->getEndDate());
                    $title     = $entry->getCaption();
                    $remarks   = $entry->getNote();
                    $moreInfos   = $entry->getInformation();
                    $link   = $entry->getLink();
                    if ($buildPDF)
                    {
                        if ($remarks != null && strlen(trim($remarks)) > 0) {
                            $title = $title.' ('.$remarks.')';
                        }
                        if ($useColors) {
                            $cal->addEntry($startDate, $endDate, $title, getContrastColor($calendar->getColor()),
                                $calendar->getColor());
                        } else {
                            $cal->addEntry($startDate, $endDate, $title, '#000000',
                                '#ffffff');
                        }
                    }
                    else
                    {
                        $myCol= 'A';
                        if ($printLegende)
                        {
                            $sheet->setCellValue($myCol.$rowPos, $calendar->getName());
                            $myCol++;
                        }
                        $sheet->getStyle($myCol.$rowPos)
                            ->getNumberFormat() 
                            ->setFormatCode( 
                            \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DATETIME 
                            ); 
                        // Convert to an Excel date/time 
                        $excelStartDate= \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel( 
                                          $startDate );  
                        $sheet->setCellValue($myCol++.$rowPos, $excelStartDate);
                        if ($printEND)
                        {
                            $sheet->getStyle($myCol.$rowPos)
                                ->getNumberFormat() 
                                ->setFormatCode( 
                                \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DATETIME 
                                ); 
                            // Convert to an Excel date/time 
                            $excelEndDate = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel( 
                                              $endDate );  
                            $sheet->setCellValue($myCol++.$rowPos, $excelEndDate);
                        }
                        $sheet->setCellValue($myCol++.$rowPos, $title);
                        $sheet->setCellValue($myCol++.$rowPos, $remarks);
                        $sheet->setCellValue($myCol++.$rowPos, $moreInfos);
                        $sheet->setCellValue($myCol.$rowPos, $link);
                        if ($printLegende )
                        {
                            if ($useColors) {
                                $sheet->getStyle("A".$rowPos.":".$myCol.$rowPos)->getFont()->getColor()->
                                        setARGB(aschild\PDFCalendarBuilder\ColorNames::html2html(getContrastColor($calendar->getColor()), false));                            
                                $sheet->getStyle("A".$rowPos.":".$myCol.$rowPos)->getFill()->
                                        setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->
                                        getStartColor()->
                                        setARGB(aschild\PDFCalendarBuilder\ColorNames::html2html($calendar->getColor(), false));
                            }
                        }
                        else if ($printFullYear)
                        {
                            if ($startDate->format("m") % 2 == 0)
                            {
                                if ($useColors) {
                                    $sheet->getStyle("A".$rowPos.":".$myCol.$rowPos)->getFill()->
                                            setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->
                                            getStartColor()->
                                            setRGB($excelEvenBGColor);
                                }
                            }
                        }
                        $rowPos++;
                    }
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
                    if ($buildPDF)
                    {
                        $cal->addEntry($startDate, $endDate, $title);
                    }
                    else
                    {
                        $myCol= 'A';
                        if ($printLegende)
                        {
                            $sheet->setCellValue($myCol++.$rowPos, $resource->getDescription());
                        }
                        // Set the number format mask so that the excel timestamp  
                        // will be displayed as a human-readable date/time 
                        $sheet->getStyle($myCol.$rowPos)
                            ->getNumberFormat() 
                            ->setFormatCode( 
                            \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DATETIME 
                            ); 
                        // Convert to an Excel date/time 
                        $excelStartDate = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel( 
                                          $startDate );  
                        $sheet->setCellValue($myCol++.$rowPos, $excelStartDate);
                        if ($printEND)
                        {
                            // Set the number format mask so that the excel timestamp  
                            // will be displayed as a human-readable date/time 
                            $sheet->getStyle($myCol.$rowPos) 
                                ->getNumberFormat() 
                                ->setFormatCode( 
                                \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DATETIME
                                ); 
                            // Convert to an Excel date/time 
                            $excelEndDate = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel( 
                                              $endDate );  
                            $sheet->setCellValue($myCol++.$rowPos, $excelEndDate);
                        }
                        $sheet->setCellValue($myCol++.$rowPos, $title);
                        $sheet->setCellValue($myCol++.$rowPos, $remark);
                        $sheet->setCellValue($myCol++.$rowPos, var_export($entry, true));
                        $rowPos++;
                    }
                }
            }
            
            if ($buildPDF)
            {
                $cal->buildCalendar();
                $cal->writeTimestamp("@".strftime('%d.%m.%Y %H:%M'), $cal->getPageWidth()-105, 10, 100);
            }
            else
            {
                $sheet->setCellValue('A'.$rowPos, "@".strftime('%d.%m.%Y %H:%M'));
            }
        }
        if ($buildPDF)
        {
            $cal->Output("calendar-".$requestedMonth->format("Y") .'-'.$requestedMonth->format("m").".pdf", "I");
        }
        else
        {
            $writer = new Xlsx($cal);
            header('Content-Type:vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition:attachment;filename="'."calendar-".$requestedMonth->format("Y") .'-'.$requestedMonth->format("m").'.xlsx"');
            header('Cache-Control:max-age=0');
            $writer->save('php://output');
        }
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
<?php } 


function invertColor($hex) {
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) !== 6) {
        return '#000000';
    }
    $new = '';
    for ($i = 0; $i < 3; $i++) {
        $rgbDigits = 255 - hexdec(substr($hex, (2 * $i), 2));
        $hexDigits = ($rgbDigits < 0) ? 0 : dechex($rgbDigits);
        $new .= (strlen($hexDigits) < 2) ? '0' . $hexDigits : $hexDigits;
    }
    return '#' . $new;
}

function getContrastColor($hexcolor) 
{               
    $r = hexdec(substr($hexcolor, 1, 2));
    $g = hexdec(substr($hexcolor, 3, 2));
    $b = hexdec(substr($hexcolor, 5, 2));
    $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
    return ($yiq >= 128) ? 'black' : 'white';
}


/**
 * Filter cal entries, depedning on public/private setting
 * 
 * @param type $calEntries array with clendar entries
 * @param type $showPublic
 * @param type $showPrivate
 */
function filterPublicPrivate($calEntries, $showPublic, $showPrivate) {
   $retVal= [];
   foreach($calEntries as $entry) {
       if ($entry->getIsInternal() && $showPrivate) {
           array_push($retVal, $entry);
       }
       if (!$entry->getIsInternal() && $showPublic) {
           array_push($retVal, $entry);
       }
   }
   return $retVal;
}