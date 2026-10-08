<?php
// relative strings, formats, and the procedural date functions read and
// write wall-clock time in the zone in effect, including before 1970
$zones = ['UTC', 'America/New_York', 'Australia/Lord_Howe', 'Asia/Kolkata', '+05:45', '-03:30'];
$starts = ['2024-03-10 01:59:59', '2024-11-03 01:30:00', '1969-12-31 23:00:00', '2024-10-06 01:45:00'];
$mods = ['+1 day', 'midnight', 'noon', '+90 minutes', 'next sunday', 'last monday', 'tomorrow', 'first day of next month', 'last day of next month', '-1 year'];
foreach ($zones as $z) {
    foreach ($starts as $st) {
        $d = new DateTimeImmutable($st, new DateTimeZone($z));
        echo "$z $st: ", $d->format('Y-m-d H:i:s T P U I z t L W o D'), "\n";
        foreach ($mods as $m) echo "  $m: ", $d->modify($m)->format('Y-m-d H:i:s T U'), "\n";
        echo "  add P1M: ", $d->add(new DateInterval('P1M'))->format('c'), "\n";
        echo "  sub P1D: ", $d->sub(new DateInterval('P1D'))->format('c'), "\n";
        echo "  diff: ", $d->diff(new DateTimeImmutable('2025-01-01 00:00:00', new DateTimeZone($z)))->format('%R %y %m %d %h %i %s %a'), "\n";
    }
}

// strtotime reads a zoneless string in the default zone
date_default_timezone_set('America/New_York');
foreach (['2024-03-10 02:30', '2024-11-03 01:30', 'tomorrow', '+1 week', '2024-07-01T12:00:00Z', '2024-07-01 12:00 +02:00'] as $s) {
    echo "strtotime($s): ", strtotime($s, 1710000000), "\n";
}

// procedural functions use the default zone, including dst and pre-1970
foreach (['UTC', 'America/New_York', 'australia/lord_howe', 'Asia/Kolkata'] as $z) {
    var_dump(date_default_timezone_set($z));
    foreach ([-100000000, -3600, 0, 1730611800, 1710035999, 4102444800, -62135596800] as $t) {
        echo $z, " ", $t, ": ";
        // windows php computes swatch beats in a 32-bit long, so only compare them in range
        if ($t >= -2147483648 && $t <= 2147483647) echo idate('B', $t), ",";
        foreach (str_split('dhHimIsLNotUwWyYzZ') as $f) echo idate($f, $t), ",";
        echo " | ", implode(",", localtime($t)), " | ", implode(",", getdate($t)), " | ", date('Y-m-d H:i:s I z t L T', $t), "\n";
    }
}
var_dump(localtime(86400 * 40, true));
var_dump(date_default_timezone_get());
var_dump(@date_default_timezone_set('+05:00'), @date_default_timezone_set('Not/AZone'), date_default_timezone_get());

// years outside 1000-9999 keep four digits; 'c' and 'r' count the sign
date_default_timezone_set('UTC');
foreach ([-62135596800, -62200000000, -99999999999, 253402300800] as $t) echo date('Y c r', $t), "\n";

// a fixed-offset zone has no abbreviation
foreach (['2024-01-01 10:00 +05:45', '2024-01-01 10:00 -03:30', '2024-01-01T10:00:00+00:00'] as $s) echo (new DateTime($s))->format('T e'), "\n";
