<?php

function fastTypedInt(int $value): int
{
    return $value;
}

function fastTypedFloat(float $value): float
{
    return $value;
}

class FastTypedParent {}
class FastTypedChild extends FastTypedParent {}

class FastTypedMethods
{
    public function parentType(FastTypedParent $value): FastTypedParent
    {
        return $value;
    }

    public static function unionType(int|string $value): int|string
    {
        return $value;
    }
}

foreach ([
    static fn() => fastTypedInt('12'),
    static fn() => fastTypedFloat(3),
    static fn() => (new FastTypedMethods())->parentType(new FastTypedChild())::class,
    static fn() => FastTypedMethods::unionType('ok'),
] as $call) {
    var_dump($call());
}

foreach ([
    static fn() => fastTypedInt([]),
    static fn() => (new FastTypedMethods())->parentType(new stdClass()),
    static fn() => FastTypedMethods::unionType([]),
] as $call) {
    try {
        $call();
    } catch (TypeError $error) {
        echo "TypeError\n";
    }
}
