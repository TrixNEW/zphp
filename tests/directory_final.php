<?php
// php 8.5 made Directory final, and only dir() creates one
$r = new ReflectionClass("Directory");
var_dump($r->isFinal(), $r->getModifiers());
try {
    new Directory;
} catch (Error $e) {
    echo get_class($e), ": ", $e->getMessage(), "\n";
}
$base = sys_get_temp_dir() . "/zphp_directory_final_" . getmypid();
mkdir($base);
touch("$base/a.txt");
$d = dir($base);
var_dump(get_class($d), $d->path === $base);
$names = [];
while (($entry = $d->read()) !== false) $names[] = $entry;
sort($names);
var_dump($names);
$d->close();
unlink("$base/a.txt");
rmdir($base);
