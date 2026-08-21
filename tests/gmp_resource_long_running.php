<?php
// GMP native integers release backing state in persistent CLI loops
$before = memory_get_usage();
for ($i = 0; $i < 50000; $i++) {
    $number = gmp_init('123456789');
    unset($number);
}
$growth = memory_get_usage() - $before;
echo ($growth < 12 * 1024 * 1024) ? "bounded\n" : "unbounded\n";
