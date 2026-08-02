<?php

foreach ([
    ['Europe/Dublin', '1940-06-01 12:00:00'],
    ['America/New_York', '1945-08-14 12:00:00'],
    ['Asia/Kathmandu', '1985-01-01 12:00:00'],
    ['Pacific/Apia', '2011-12-30 12:00:00'],
] as [$zone, $time]) {
    $date = new DateTime($time, new DateTimeZone($zone));
    echo $zone, ' ', $time, ' => ', $date->format('Y-m-d H:i:s P T U'), "\n";
}
