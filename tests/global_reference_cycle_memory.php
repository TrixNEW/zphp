<?php
// global self-reference cycles collect without breaking surviving aliases
$cycle = [];
$cycle['self'] = &$cycle;
$alias = &$cycle;
unset($cycle);
$alias['value'] = 7;
echo $alias['self']['value'], "\n";
unset($alias);
echo gc_collect_cycles(), "\n";

$before = memory_get_usage();
$collected = 0;
for ($phase = 0; $phase < 5; $phase++) {
    for ($i = 0; $i < 4000; $i++) {
        $cycle = [];
        $cycle['self'] = &$cycle;
        unset($cycle);
    }
    $collected += gc_collect_cycles();
}
$growth = memory_get_usage() - $before;
echo $collected, "\n";
echo ($growth < 18 * 1024 * 1024) ? "bounded\n" : "unbounded\n";
