<?php
// printf-family width, precision and argument-number specifiers: php's range
// and type errors, the 53-digit float precision cap, precision -1 for the %g
// family, and %h/%H

set_error_handler(function (int $no, string $message) {
    echo "notice: $message\n";
    return true;
}, E_NOTICE);

$cases = [
    ['%.99999999999999999999f', 1.0], ['%.2147483648f', 1.0], ['%.2147483647f', 1.0],
    ['%99999999999999999999d', 1], ['%2147483648d', 1],
    ['%.*f', -1, 1.5], ['%.*f', -2, 1.5], ['%.*f', 2147483648, 1.5],
    ['%*d', -3, 1], ['%*d', 2147483648, 1], ['%*d', 'x', 1], ['%.*f', '2', 1.5], ['%*d', 5, 42], ['%-*d|', 5, 42],
    ['%0$s', 'a'], ['%99999999999$s', 'a'], ['%2$s %1$s', 'a', 'b'],
    ['%.60f', 1.5], ['%.54e', 1.5], ['%.53f', 0.1],
    ['%.*g', -1, 1 / 3], ['%.*G', -1, 1e25], ['%.*g', -1, 0.1], ['%.*g', -1, -123456.789], ['%.*g', -1, 1e-7], ['%.*g', -1, 2.5e16], ['%.*H', -1, 12345678901234567890],
    ['%h', 1 / 3], ['%.3h', 1234.5678], ['%H', 1e20], ['%10.4h|', 3.14159], ['%.*h', -1, 0.0],
];
foreach ($cases as $case) {
    try {
        $out = sprintf(...$case);
        echo $case[0], ' => ', strlen($out) > 80 ? strlen($out) . ' bytes' : $out, "\n";
    } catch (Throwable $e) {
        echo $case[0], ' => ', get_class($e), ': ', $e->getMessage(), "\n";
    }
}
