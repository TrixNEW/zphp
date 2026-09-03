<?php
// IntlDateFormatter releases its native formatter in persistent CLI loops
$before = memory_get_usage();
for ($i = 0; $i < 50000; $i++) {
    $formatter = new IntlDateFormatter(
        'en_US',
        IntlDateFormatter::SHORT,
        IntlDateFormatter::SHORT,
        'UTC'
    );
    unset($formatter);
}
$growth = memory_get_usage() - $before;
echo ($growth < 24 * 1024 * 1024) ? "bounded\n" : "unbounded\n";
