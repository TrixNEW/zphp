<?php
// A script-frame COW write must release only the frame's owning reference,
// while functions read the current canonical superglobal binding.
$_SERVER = array_merge($_SERVER, ['lifetime_probe' => true]);
function readRequestFilename() {
    return $_SERVER['SCRIPT_FILENAME'] ?? 'missing';
}
// Statement boundaries drain the old array after separation.
$noise = ['one', 'two'];
unset($noise);
echo basename(readRequestFilename()), "\n";

function readLifetimeProbe() { return $_SERVER['lifetime_probe'] ?? false; }
echo readLifetimeProbe() ? "probe visible\n" : "probe missing\n";
