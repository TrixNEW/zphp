<?php
// `cmd` runs the command through the shell like shell_exec(), interpolating
// variables as a double-quoted string does
$name = "wor ld";
$a = ["k" => "v"];
var_dump(`printf '%s' hi`);
var_dump(`echo $name`);
var_dump(`echo {$a["k"]} \$NOT_EXPANDED_BY_PHP x\ty`);
var_dump(`printf '%s' '\`'`);
var_dump(`true`);
$cmd = "printf";
var_dump(`$cmd '[%s]' a b`);

// the command's stderr goes straight to stderr
var_dump(`ls /zphp-no-such-dir 2>&1 >/dev/null | wc -l | tr -d ' '`);
`echo to-stderr >&2`;

// an empty command is a ValueError in every shell function
foreach (['shell_exec', 'exec', 'system', 'passthru'] as $f) {
    try {
        $f('');
    } catch (ValueError $e) {
        echo $e->getMessage(), "\n";
    }
}
