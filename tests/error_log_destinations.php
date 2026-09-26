<?php
// diagnostics and error_log() go to the error_log file when one is set, each
// line stamped; log_errors=0 keeps diagnostics out of the log

ini_set('display_errors', '0');
$log = tempnam(sys_get_temp_dir(), 'zphp-log');
ini_set('error_log', $log);
trigger_error('first', E_USER_WARNING);
error_log('plain message');
ini_set('log_errors', '0');
trigger_error('not logged', E_USER_NOTICE);
error_log('still logged by error_log()');
ini_set('log_errors', '1');
$missing = [];
echo $missing["key"] ?? "", $missing["key"];
foreach (file($log) as $line) {
    echo preg_match('/^\[\d{2}-[A-Z][a-z]{2}-\d{4} \d{2}:\d{2}:\d{2} [^\]]+\] (.*)$/s', $line, $m) ? $m[1] : "unstamped: $line";
}
unlink($log);
