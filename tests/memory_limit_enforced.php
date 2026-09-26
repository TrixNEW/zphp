<?php
// memory_limit is enforced: the script stops with php's fatal, shutdown
// functions still run and see it through error_get_last()

ini_set('log_errors', '0');
ini_set('display_errors', '0');
// the configured default varies between installs
ini_set('memory_limit', '128M');

set_error_handler(function (int $no, string $message) {
    echo "warning: $message\n";
    return true;
}, E_WARNING);
var_dump(ini_set('memory_limit', '1K'), ini_get('memory_limit'));
var_dump(ini_set('memory_limit', 'abc'));
var_dump(ini_set('memory_limit', '256MB'), ini_get('memory_limit'));
var_dump(ini_set('memory_limit', '0x10M'), ini_get('memory_limit'));
restore_error_handler();

$before = memory_get_usage();
$big = str_repeat('x', 1 << 20);
var_dump(memory_get_usage() - $before >= 1 << 20, memory_get_peak_usage() - $before >= 1 << 20);
unset($big);
var_dump(memory_get_usage() - $before < 1 << 16);
memory_reset_peak_usage();
var_dump(memory_get_peak_usage() - $before < 1 << 16, memory_get_usage(true) % (2 << 20));

register_shutdown_function(function () {
    $e = error_get_last();
    echo 'shutdown: ', $e['type'] ?? '-', ' ', strstr($e['message'] ?? '', ' (tried', true), "\n";
});
ini_set('memory_limit', '8M');
$rows = [];
for ($i = 0; $i < 10000000; $i++) {
    $rows[] = ['id' => $i, 'name' => "row $i"];
}
echo "not reached\n";
