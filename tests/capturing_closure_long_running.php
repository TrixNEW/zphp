<?php
// unreachable capturing closures release metadata; live aliases and captures survive
$before = memory_get_usage();
$sum = 0;
for ($i = 0; $i < 20000; $i++) {
    $base = $i;
    $closure = static fn(int $value): int => $base + $value;
    $sum += $closure(1);
    unset($closure);
}
echo $sum, "\n";
echo (memory_get_usage() - $before < 4 * 1024 * 1024) ? "bounded\n" : "unbounded\n";

$closures = [];
for ($i = 0; $i < 2000; $i++) {
    $captured = $i;
    $closures[] = static fn(): int => $captured;
}
echo $closures[0](), " ", $closures[1999](), "\n";

$value = 1;
$increment = function () use (&$value): int { return ++$value; };
$alias = $increment;
unset($increment);
echo $alias(), " ", $value, "\n";
