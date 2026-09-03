<?php
// closed process resources release child state and reuse object shells
$before = memory_get_usage();
for ($i = 0; $i < 10000; $i++) {
    $process = proc_open('true', [], $pipes);
    proc_close($process);
    unset($process, $pipes);
}
$growth = memory_get_usage() - $before;
echo ($growth < 12 * 1024 * 1024) ? "bounded\n" : "unbounded\n";
