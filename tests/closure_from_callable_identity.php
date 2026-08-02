<?php

function closureFromCallableIdentityFunction(string $value): string
{
    return strtoupper($value);
}

class ClosureFromCallableIdentityTarget
{
    public function method(string $value): string
    {
        return 'method:' . $value;
    }
}

$target = new ClosureFromCallableIdentityTarget();
$function = Closure::fromCallable('closureFromCallableIdentityFunction');
$method = Closure::fromCallable([$target, 'method']);
$invokable = new class {
    public function __invoke(string $value): string
    {
        return 'invoke:' . $value;
    }
};
$invoke = Closure::fromCallable($invokable);

foreach ([$function, $method, $invoke] as $closure) {
    echo get_debug_type($closure), ' ', $closure instanceof Closure ? 'yes' : 'no', "\n";
}

echo $function('ok'), "\n";
echo $method('ok'), "\n";
echo $invoke('ok'), "\n";
echo $invoke === $invokable ? "same\n" : "different\n";
echo Closure::fromCallable($function) === $function ? "same\n" : "different\n";
