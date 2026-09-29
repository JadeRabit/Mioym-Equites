<?php
/**
 * calendar.php
 * Generates an .ics file or redirects to web calendars (Google, Outlook, Yahoo)
 * Usage: calendar.php?id=123&type=ics
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : 'ics';

if ($id <= 0) {
    die("Invalid webinar ID.");
}

// Fetch webinar details
$stmt = $pdo->prepare("SELECT * FROM webinar_tbl WHERE webinar_id = ?");
$stmt->execute([$id]);
$webinar = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$webinar) {
    die("Webinar not found.");
}

$title = $webinar['title'] ?: 'Mioym Equities Webinar';
$location = $webinar['webinar_link'] ?: 'https://mioymequities.com';
$description = "Join our webinar: " . $title . "\n\nLink: " . $location . "\n\nLearn about our " . get_setting('annual_return', '15%') . " return strategy.";

try {
    $eventTime = new DateTime($webinar['schedule_date&time']);
} catch (Exception $e) {
    die("Invalid schedule date.");
}

// UTC Time conversion for standard calendar format
$utcTime = clone $eventTime;
$utcTime->setTimezone(new DateTimeZone('UTC'));
$startTime = $utcTime->format('Ymd\THis\Z');

$endTime = clone $utcTime;
// Parse duration or default to 60 minutes
$durationStr = $webinar['duration'] ?? '60-minute';
$minutes = 60;
if (preg_match('/(\d+)/', $durationStr, $matches)) {
    $minutes = (int)$matches[1];
}
$endTime->modify('+' . $minutes . ' minutes');
$endTimeStr = $endTime->format('Ymd\THis\Z');

// Google Calendar
if ($type === 'google') {
    // Use local wall-clock time from DB for Google as well (no UTC shift).
    $googleStart = $eventTime->format('Ymd\THis');
    $googleEnd = clone $eventTime;
    $googleEnd->modify('+' . $minutes . ' minutes');
    $googleEndStr = $googleEnd->format('Ymd\THis');
    $url = "https://www.google.com/calendar/render?action=TEMPLATE&text=" . urlencode($title) . "&dates={$googleStart}/{$googleEndStr}&details=" . urlencode($description) . "&location=" . urlencode($location);
    header("Location: $url");
    exit;
}

// Outlook Web Calendar
if ($type === 'outlook') {
    // Use local wall-clock time from DB (no timezone conversion) to keep exact displayed time.
    $outlookStart = urlencode($eventTime->format('Y-m-d\TH:i:s'));
    $url = "https://outlook.live.com/calendar/0/deeplink/compose?path=/calendar/action/compose&rru=addevent&startdt={$outlookStart}&subject=" . urlencode($title) . "&body=" . urlencode($description) . "&location=" . urlencode($location) . "&prompt=select_account";
    header("Location: $url");
    exit;
}

// Yahoo Calendarg diko masasagot 
if ($type === 'yahoo') {
    $url = "https://calendar.yahoo.com/?v=60&view=d&type=20&title=" . urlencode($title) . "&st={$startTime}&et={$endTimeStr}&desc=" . urlencode($description) . "&in_loc=" . urlencode($location);
    header("Location: $url");
    exit;
}

// Default to ICS (Apple, Outlook Desktop)
$icsContent = "BEGIN:VCALENDAR\r\n";
$icsContent .= "VERSION:2.0\r\n";
$icsContent .= "PRODID:-//Mioym Equities//Webinar//EN\r\n";
$icsContent .= "CALSCALE:GREGORIAN\r\n";
$icsContent .= "METHOD:PUBLISH\r\n";
$icsContent .= "BEGIN:VEVENT\r\n";
$icsContent .= "DTSTART:{$startTime}\r\n";
$icsContent .= "DTEND:{$endTimeStr}\r\n";
$icsContent .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
$icsContent .= "UID:" . md5($webinar['webinar_id'] . $startTime) . "@mioymequities.com\r\n";
$icsContent .= "SUMMARY:" . $title . "\r\n";
$icsContent .= "DESCRIPTION:" . str_replace("\n", "\\n", $description) . "\r\n";
$icsContent .= "LOCATION:" . $location . "\r\n";
$icsContent .= "STATUS:CONFIRMED\r\n";
$icsContent .= "SEQUENCE:0\r\n";
$icsContent .= "END:VEVENT\r\n";
$icsContent .= "END:VCALENDAR\r\n";

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename="webinar-' . $webinar['webinar_id'] . '.ics"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
echo $icsContent;
exit;
