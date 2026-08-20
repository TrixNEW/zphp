<?php
// long-running CLI arrays should reuse released container structs
$before = memory_get_usage();
for ($i = 0; $i < 200000; $i++) {
    $array = [$i, $i + 1, $i + 2];
    unset($array);
}
$growth = memory_get_usage() - $before;
echo ($growth < 4 * 1024 * 1024) ? "bounded\n" : "unbounded\n";
