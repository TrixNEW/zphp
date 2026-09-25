<?php
// closed process resources release child state and reuse object shells
function spawnAndClose(int $n): void
{
    for ($i = 0; $i < $n; $i++) {
        $process = proc_open('true', [], $pipes);
        proc_close($process);
        unset($process, $pipes);
    }
}

spawnAndClose(200);
$before = memory_get_usage();
spawnAndClose(1000);
$growth = memory_get_usage() - $before;
echo ($growth < 1024 * 1024) ? "bounded\n" : "unbounded\n";
