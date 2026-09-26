<?php
function traced($value, ...$more) {
    debug_print_backtrace();
    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    return array_map(fn($f) => basename($f['file'] ?? '-') . ':' . ($f['line'] ?? '-') . ':' . ($f['class'] ?? '') . ($f['type'] ?? '') . $f['function'], debug_backtrace());
}
class Tracer {
    public function run(array $items) { return traced('a string longer than fifteen', $items, $this, null, true, 1.5, fn() => 1); }
    public static function start() { return (new Tracer)->run([1, 2]); }
}
