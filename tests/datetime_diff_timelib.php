<?php
// diff() between instants with microseconds, in one zone and across zones,
// offsets, and abbreviations, clustered around dst transitions (timelib_diff)
$zones = ['America/New_York', 'Europe/Paris', 'Australia/Sydney', 'Australia/Lord_Howe', 'UTC', '+05:30', '-03:00', 'CEST', 'EST', 'Asia/Kolkata', 'America/Sao_Paulo'];
$anchors = [1710054000, 1730608200, 1711846800, 1729990800, 1712419200, 1728187200, 1700000000, 951782400, 1583020800];
$seed = 12345;
$rand = function ($n) use (&$seed) { $seed = ($seed * 1103515245 + 12345) % 2147483648; return $seed % $n; };
for ($k = 0; $k < 1500; $k++) {
    $z1 = $zones[$rand(count($zones))];
    $z2 = $rand(3) == 0 ? $zones[$rand(count($zones))] : $z1;
    $spans = [0, 1, 59, 3600, 5400, 86400, 86400 * 31, 86400 * 400, 7200, 1800];
    $t1 = $anchors[$rand(count($anchors))] + ($rand(2) ? 1 : -1) * $spans[$rand(count($spans))] + $rand(7200) - 3600;
    $t2 = $t1 + ($rand(2) ? 1 : -1) * ($spans[$rand(count($spans))] + $rand(90000));
    $u1 = $rand(4) == 0 ? 0 : $rand(1000000);
    $u2 = $rand(4) == 0 ? 0 : $rand(1000000);
    $a = new DateTimeImmutable(sprintf('@%d.%06d', $t1, $u1))->setTimezone(new DateTimeZone($z1));
    $b = new DateTimeImmutable(sprintf('@%d.%06d', $t2, $u2))->setTimezone(new DateTimeZone($z2));
    $d = $a->diff($b);
    echo $a->format('Y-m-d H:i:s.u T'), ' -> ', $b->format('Y-m-d H:i:s.u T'), ': ', $d->format('%R %y %m %d %h %i %s %F %a'), "\n";
}
