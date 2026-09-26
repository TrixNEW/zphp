<?php
// foreach over a plain object keeps the object alive for the loop and frees
// it when the loop ends, however the loop is left

class Tracked {
    public $a = 1;
    public $b = 2;
    function __construct(public string $label) {}
    function __destruct() { echo "destruct {$this->label}\n"; }
}

foreach (new Tracked('plain') as $k => $v) echo "$k\n";
echo "after plain\n";

foreach (new Tracked('break') as $k => $v) {
    echo "$k\n";
    break;
}
echo "after break\n";

function early() {
    foreach (new Tracked('return') as $k => $v) {
        return $k;
    }
}
echo early(), "\n";
echo "after return\n";

try {
    foreach (new Tracked('throw') as $k => $v) throw new RuntimeException("from $k");
} catch (RuntimeException $e) {
    echo $e->getMessage(), "\n";
}
echo "after throw\n";

foreach (new Tracked('outer') as $k => $v) {
    foreach (new Tracked("inner $k") as $k2 => $v2) {}
    echo "outer $k\n";
}
echo "done\n";

$start = memory_get_usage();
for ($i = 0; $i < 2000; $i++) {
    $o = new stdClass;
    $o->x = $i;
    foreach ($o as $v) {}
}
echo memory_get_usage() - $start < 16 * 1024 ? "flat\n" : "grew\n";
