<?php
// long-running CLI generators should release their internal buffers
function retiredValues(int $value): Generator
{
    yield $value;
    yield $value + 1;
}

$before = memory_get_usage();
for ($i = 0; $i < 20000; $i++) {
    foreach (retiredValues($i) as $value) {
    }
    unset($value);
}
$growth = memory_get_usage() - $before;
echo ($growth < 4 * 1024 * 1024) ? "bounded\n" : "unbounded\n";
