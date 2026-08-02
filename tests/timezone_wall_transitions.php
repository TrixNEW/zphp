<?php

foreach ([
    ['Australia/Lord_Howe', '2026-10-04 02:10:00'],
    ['Australia/Lord_Howe', '2026-04-05 01:45:00'],
    ['Pacific/Chatham', '2026-09-27 02:50:00'],
    ['Pacific/Chatham', '2026-04-05 03:15:00'],
] as [$zone, $time]) {
    $date = new DateTime($time, new DateTimeZone($zone));
    echo $zone, ' ', $time, ' => ', $date->format('Y-m-d H:i:s P U'), "\n";
}
