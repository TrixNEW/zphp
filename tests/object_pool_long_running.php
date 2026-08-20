<?php
// long-running CLI plain PHP objects should reuse released container structs
class PooledValue
{
    public int $value;
}

$before = memory_get_usage();
for ($i = 0; $i < 50000; $i++) {
    $object = new PooledValue();
    $object->value = $i;
    unset($object);
}
$growth = memory_get_usage() - $before;
echo ($growth < 4 * 1024 * 1024) ? "bounded\n" : "unbounded\n";
