<?php
// automatic cycle collection and GC controls
class AutomaticCycle
{
    public $self;
}

$before = gc_status();
for ($i = 0; $i < 10010; $i++) {
    $cycle = new AutomaticCycle();
    $cycle->self = $cycle;
    unset($cycle);
}
$after = gc_status();
echo ($after['runs'] > $before['runs'] ? "auto-ran\n" : "not-run\n");
echo ($after['collected'] > $before['collected'] ? "auto-collected\n" : "not-collected\n");

gc_disable();
echo gc_enabled() ? "enabled\n" : "disabled\n";
$disabledBefore = gc_status();
for ($i = 0; $i < 10010; $i++) {
    $cycle = new AutomaticCycle();
    $cycle->self = $cycle;
    unset($cycle);
}
$disabledAfter = gc_status();
echo ($disabledAfter['runs'] === $disabledBefore['runs'] ? "stayed-disabled\n" : "ran-disabled\n");
gc_enable();
echo gc_enabled() ? "enabled\n" : "disabled\n";
gc_collect_cycles();
