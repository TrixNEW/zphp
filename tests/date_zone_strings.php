<?php
// zone strings read the way php's parse_zone reads them: abbreviations keep a
// fixed offset and dst flag, offsets normalize, ids keep their spelling
foreach (["europe/paris", "cest", "utc", "UTC", "+0530", "GMT+5", "+05:30:15", "EST", "foo", "+100", "Europe/Paris x", "(CET)", " UTC", "", "+99:00", "+100:00", "Z", "CET", "a", "AEST", "America/Argentina/Buenos_Aires"] as $n) {
    try {
        $z = new DateTimeZone($n);
        echo "$n => ", $z->getName(), " ", $z->getOffset(new DateTime("2024-07-01 00:00 UTC")), " ", $z->getOffset(new DateTime("2024-01-01 00:00 UTC")), "\n";
    } catch (Throwable $e) {
        echo "$n => ", get_class($e), ": ", $e->getMessage(), "\n";
    }
}
var_dump(@timezone_open("nope"));
echo timezone_open("cest")->getName(), "\n";

// an abbreviation zone is a fixed offset, even in the other half of the year
$d = new DateTime("2024-07-01 12:00", new DateTimeZone("CET"));
echo $d->format("c T e I Z"), "\n";
$d = new DateTime("2024-07-01 12:00 CEST");
echo $d->format("c T e I Z U"), "\n";
$d->modify("+6 months");
echo $d->format("c T e I Z U"), "\n";
$d = new DateTimeImmutable("2024-07-01 12:00:00.25 +05:30");
echo $d->format("c T e u"), " ", $d->modify("+1 day")->format("c u"), "\n";
$d = new DateTimeImmutable("2024-01-01 00:00 EDT");
echo $d->format("c T e I"), " ", $d->setTimezone(new DateTimeZone("America/New_York"))->format("c T"), "\n";

// "@ts" moves the object to +00:00; other zones in a modify string are ignored
$d = new DateTimeImmutable("2024-03-01 10:00", new DateTimeZone("Asia/Tokyo"));
echo $d->modify("@86400")->format("c e"), " ", $d->modify("12:00 Europe/Paris")->format("c e"), "\n";

// dates past the last listed transition follow the zone's posix rule
date_default_timezone_set("America/New_York");
foreach ([4102444800, 13294342923, 30781454604, 46905116340] as $t) echo date("c T I", $t), "\n";
date_default_timezone_set("Australia/Sydney");
foreach ([4102444800, 13294342923] as $t) echo date("c T I", $t), "\n";

// parse errors: the constructor throws, modify() throws, date_modify() warns
try {
    new DateTime("2024-13-45 xx");
} catch (DateMalformedStringException $e) {
    echo $e->getMessage(), "\n";
}
try {
    (new DateTime())->modify("foo bar");
} catch (DateMalformedStringException $e) {
    echo $e->getMessage(), "\n";
}
try {
    $unused = (new DateTimeImmutable())->modify("+1 blorp");
} catch (DateMalformedStringException $e) {
    echo $e->getMessage(), "\n";
}
set_error_handler(function ($no, $str) { echo "warning: $str\n"; return true; });
var_dump(date_modify(new DateTime(), "foo"));
restore_error_handler();
print_r(DateTime::getLastErrors());
var_dump(date_create("nonsense"), date_create_immutable("2024-02-30") instanceof DateTimeImmutable);
print_r(DateTime::getLastErrors());
new DateTime("2024-01-01");
var_dump(DateTime::getLastErrors());
