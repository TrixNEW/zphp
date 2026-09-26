<?php
// instances of built-in classes are reclaimed when their last reference goes,
// like user objects, so a loop that makes and drops them runs in flat memory

class Bag extends ArrayObject {}

$cases = [
    'stdClass' => fn() => new stdClass,
    'stdClass with a property' => function () { $o = new stdClass; $o->a = 1; return $o; },
    'ArrayObject' => fn() => new ArrayObject([1, 2]),
    'ArrayObject subclass' => fn() => new Bag([1]),
    'ArrayIterator' => fn() => new ArrayIterator([]),
    'SplStack' => fn() => new SplStack,
    'SplObjectStorage' => function () { $s = new SplObjectStorage; $s[new stdClass] = 1; return $s; },
    'DateTime' => fn() => new DateTime('2020-01-01'),
    'DateInterval' => fn() => new DateInterval('P1D'),
    'ReflectionClass' => fn() => new ReflectionClass('ArrayObject'),
    'Closure' => fn() => fn() => 1,
    'Exception' => fn() => new RuntimeException('m'),
];
foreach ($cases as $name => $make) {
    for ($i = 0; $i < 200; $i++) $make();
    $before = memory_get_usage();
    for ($i = 0; $i < 3000; $i++) $make();
    $grown = memory_get_usage() - $before;
    echo str_pad($name, 26), $grown < 16 * 1024 ? "flat" : "grew $grown bytes", "\n";
}
