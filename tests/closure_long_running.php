<?php
// global capture-free closures should not allocate per-instance metadata
$before = memory_get_usage();
$sum = 0;
for ($i = 0; $i < 20000; $i++) {
    $closure = static fn(int $value): int => $value + 1;
    $sum += $closure($i);
    unset($closure);
}
$growth = memory_get_usage() - $before;
echo $sum, "\n";
echo ($growth < 4 * 1024 * 1024) ? "bounded\n" : "unbounded\n";
