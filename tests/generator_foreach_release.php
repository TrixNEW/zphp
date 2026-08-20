<?php
// completed foreach generators release the iterable stack owner
function releaseValues(): Generator
{
    yield 1;
    yield 2;
}

$before = memory_get_usage();
for ($i = 0; $i < 20000; $i++) {
    foreach (releaseValues() as $value) {
    }
}
$growth = memory_get_usage() - $before;
echo ($growth < 35 * 1024 * 1024) ? "released\n" : "leaked\n";
