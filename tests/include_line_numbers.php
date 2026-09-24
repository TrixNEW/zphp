<?php
require __DIR__ . '/include/line_numbers.php';

set_error_handler(function (int $no, string $msg, string $file, int $line) {
    echo basename($file), ':', $line, ' ', $msg, "\n";
    return true;
});
lines_warn();
restore_error_handler();

try {
    new LinesThrower();
} catch (RuntimeException $e) {
    echo basename($e->getFile()), ':', $e->getLine(), "\n";
    foreach ($e->getTrace() as $frame) {
        echo '  ', basename($frame['file'] ?? '-'), ':', $frame['line'] ?? '-', ' ', $frame['function'], "\n";
    }
}

try {
    lines_typed('nope');
} catch (TypeError $e) {
    echo str_replace(__DIR__ . '/', '', $e->getMessage()), "\n";
    echo basename($e->getFile()), ':', $e->getLine(), "\n";
}

print_r(lines_trace());
