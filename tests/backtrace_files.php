<?php
// debug_backtrace and debug_print_backtrace name each frame's own file and
// print class, call type and arguments the way php's traces do

require __DIR__ . '/backtrace_files.inc';

function entry() {
    return Tracer::start();
}
$frames = entry();
echo implode("\n", array_map(fn($f) => str_replace(__DIR__, '', $f), $frames)), "\n";
