<?php
// include and require resolve names the way php does: one canonical path per
// file for require_once and get_included_files, include_path entries before
// the running file's directory, and php's diagnostics when nothing is found

$root = __DIR__ . '/include/resolution';
$show = fn($path) => str_replace('\\', '/', str_replace(__DIR__, '', $path));

var_dump(require_once $root . '/once.php');
var_dump(require_once $root . '/./once.php');
var_dump(require_once $root . '/sub/../once.php');
var_dump(include_once $root . '/lib/../once.php');
echo "loads: {$GLOBALS['once_loads']}\n";

var_dump(require $root . '/sub/nested.php');

chdir($root . '/sub');
var_dump(require_once '../once.php');
var_dump(require 'sibling.php');
chdir(__DIR__);

$previous = set_include_path($root . '/lib' . PATH_SEPARATOR . '.');
var_dump(is_string($previous));
var_dump($show(get_include_path()) === $show(ini_get('include_path')));
var_dump(include 'found.php');
var_dump($show(stream_resolve_include_path('found.php')));
var_dump(stream_resolve_include_path('missing.php'));
var_dump(file_get_contents('data.txt', true));
var_dump(file('data.txt', FILE_USE_INCLUDE_PATH | FILE_IGNORE_NEW_LINES));
$h = fopen('data.txt', 'r', true);
var_dump(fgets($h));
fclose($h);
var_dump(@file_get_contents('data.txt'));
var_dump(set_include_path(''));

set_include_path('.');
set_error_handler(function ($no, $msg) {
    echo "warning: $msg\n";
    return true;
});
var_dump(include 'missing.php');
var_dump(include_once 'missing.php');
try {
    require 'missing.php';
} catch (Error $e) {
    echo get_class($e), ": ", $e->getMessage(), "\n";
}
try {
    require_once 'missing.php';
} catch (Error $e) {
    echo get_class($e), ": ", $e->getMessage(), "\n";
}
try {
    include '';
} catch (ValueError $e) {
    echo get_class($e), ": ", $e->getMessage(), "\n";
}
restore_error_handler();

foreach (get_included_files() as $file) echo $show($file), "\n";
