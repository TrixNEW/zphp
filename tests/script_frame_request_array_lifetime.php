<?php
// A script-frame COW write must release only the frame's owning reference,
// not the independent request root used when a function reads superglobals.
$_SERVER = array_merge($_SERVER, ['lifetime_probe' => true]);
function readRequestFilename() {
    return $_SERVER['SCRIPT_FILENAME'] ?? 'missing';
}
// Statement boundaries drain the old array after separation.
$noise = ['one', 'two'];
unset($noise);
echo basename(readRequestFilename()), "\n";
