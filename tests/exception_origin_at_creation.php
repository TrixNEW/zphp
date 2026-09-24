<?php
class Silent extends Exception
{
    public function __construct()
    {
    }
}

class Wrapped extends RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct(strtoupper($message), 7);
    }
}

class Factory
{
    public static function make(string $what): Exception
    {
        return new Wrapped($what);
    }

    public function __construct(public int $depth)
    {
        if ($depth > 0) {
            throw new LogicException("depth $depth");
        }
    }
}

function where(Throwable $e): string
{
    $frames = array_map(
        fn($f) => ($f['class'] ?? '') . ($f['type'] ?? '') . $f['function'] . '@' . ($f['line'] ?? '-') . '(' . implode(',', array_map('json_encode', $f['args'] ?? [])) . ')',
        $e->getTrace()
    );
    return basename($e->getFile()) . ':' . $e->getLine() . ' [' . implode(' ', $frames) . ']';
}

$silent = new Silent();
echo where($silent), "\n";

$made = Factory::make('boom');
echo $made->getMessage(), ' ', $made->getCode(), ' ', where($made), "\n";

try {
    new Factory(2);
} catch (LogicException $e) {
    echo where($e), "\n";
}

function typed(int $n): int
{
    return $n;
}

function relay(string $value): int
{
    return typed($value);
}

try {
    relay('x');
} catch (TypeError $e) {
    echo where($e), "\n";
}

$closure = function (array $items) {
    return new DomainException(count($items) . ' items');
};
echo where($closure([1, 2])), "\n";
echo get_class(unserialize(serialize(new Silent()))), "\n";
