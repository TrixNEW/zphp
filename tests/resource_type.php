<?php
// resources are their own type: presentation, casts, comparison, closed state, ids
$f = fopen('php://memory', 'r+');
var_dump($f, get_resource_id($f), get_resource_type($f), gettype($f), get_debug_type($f));
var_dump(is_resource($f), is_object($f), is_scalar($f), (bool) $f, (int) $f, (float) $f, (string) $f, "x{$f}y");
var_dump($f == $f, $f === $f, $f == STDIN, $f <=> STDIN, $f == get_resource_id($f));
print_r([$f]);
echo "\n", var_export([$f], true), "\n", serialize([$f]), "\n";
var_dump(json_encode($f), json_last_error_msg());
var_dump((array) $f, STDIN, STDOUT, STDERR);
$ctx = stream_context_create(['http' => ['method' => 'POST']]);
var_dump($ctx, get_resource_type($ctx), stream_context_get_options($ctx));
$d = opendir(__DIR__);
var_dump(get_resource_type($d), readdir() !== false);
closedir();
var_dump(is_resource($d), gettype($d), get_resource_type($d));
$p = proc_open([PHP_BINARY, '-r', 'echo 1;'], [1 => ['pipe', 'w']], $pipes);
var_dump($p, $pipes, stream_get_contents($pipes[1]), proc_close($p), $p, $pipes);
fclose($f);
var_dump($f, is_resource($f), gettype($f), get_debug_type($f), (bool) $f, (int) $f);
foreach ([fn () => fread($f, 1), fn () => fclose($f), fn () => fwrite('x', 'y'), fn () => get_class($f), fn () => spl_object_id($f), fn () => $f + 1, fn () => clone $f, fn () => $f->m(), fn () => proc_close($p), fn () => get_resource_type(5)] as $call) {
    try {
        $call();
    } catch (Error $e) {
        echo get_class($e), ': ', $e->getMessage(), "\n";
    }
}
$t = tmpfile();
fwrite($t, 'temp');
rewind($t);
var_dump(fread($t, 10), stream_get_meta_data($t)['mode']);
$a = [];
$a[STDIN] = 'in';
var_dump($a, isset($a[STDIN]));
