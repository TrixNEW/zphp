<?php

function firstClassCallableIdentityFunction(string $value): string
{
    return strtoupper($value);
}

class FirstClassCallableIdentityTarget
{
    public function instance(string $value): string
    {
        return 'instance:' . $value;
    }

    public static function staticMethod(string $value): string
    {
        return 'static:' . $value;
    }
}

$target = new FirstClassCallableIdentityTarget();
$functionA = firstClassCallableIdentityFunction(...);
$functionB = firstClassCallableIdentityFunction(...);
$instance = $target->instance(...);
$static = FirstClassCallableIdentityTarget::staticMethod(...);

foreach ([$functionA, $instance, $static] as $closure) {
    echo get_debug_type($closure), ' ', get_class($closure), ' ', $closure instanceof Closure ? 'yes' : 'no', "\n";
}

echo $functionA === $functionB ? "same\n" : "different\n";
$functionCopy = $functionA;
echo $functionA === $functionCopy ? "same\n" : "different\n";
echo $functionA('ok'), "\n";
echo $instance('ok'), "\n";
echo $static('ok'), "\n";

foreach ([$functionA, $instance, $static] as $closure) {
    try {
        serialize($closure);
    } catch (Exception $error) {
        echo get_class($error), "\n";
    }
}
