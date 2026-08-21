<?php
// fibers, exceptions, and transient callbacks stay bounded in a persistent CLI loop
$before = memory_get_usage();
$checksum = 0;
for ($phase = 0; $phase < 5; $phase++) {
    for ($i = 0; $i < 2000; $i++) {
        $base = $phase * 2000 + $i;
        $fiber = new Fiber(static function () use ($base): int {
            Fiber::suspend($base + 1);
            return $base + 2;
        });
        $checksum += $fiber->start();
        $fiber->resume();
        $checksum += $fiber->getReturn();

        try {
            throw new RuntimeException("event $base", $base);
        } catch (RuntimeException $exception) {
            $checksum += $exception->getCode();
        }

        $handler = static function (int $errno, string $message) use ($base, &$checksum): bool {
            $checksum += $base + $errno + strlen($message);
            return true;
        };
        set_error_handler($handler);
        trigger_error('x', E_USER_NOTICE);
        restore_error_handler();
        unset($handler, $exception, $fiber);
    }
    gc_collect_cycles();
}
$growth = memory_get_usage() - $before;
echo $checksum, "\n";
echo ($growth < 20 * 1024 * 1024) ? "bounded\n" : "unbounded\n";
