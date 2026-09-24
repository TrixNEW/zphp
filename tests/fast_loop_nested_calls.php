<?php
function typed(int $n): int
{
    return $n * 2;
}

function relay(): int
{
    return typed("x");
}

function coerced(): int
{
    return typed("21");
}

function defaults(int $a = 5, array $b = [], string $c = PHP_EOL): string
{
    return $a + count($b) . $c;
}

function withDefaults(): string
{
    return defaults() . defaults(1, [1, 2]);
}

function add($a, $b)
{
    return $a + $b;
}

function less($a, $b): bool
{
    return $a < $b;
}

class Noisy
{
    public function __construct(public string $name)
    {
    }

    public function __destruct()
    {
        echo "destruct {$this->name}\n";
        typed(1);
    }
}

function churn(): void
{
    for ($i = 0; $i < 3; $i++) {
        $n = new Noisy("n$i");
        $n = null;
    }
}

try {
    relay();
} catch (TypeError $e) {
    echo $e->getMessage(), "\n";
}
echo coerced(), "\n";
echo withDefaults();
echo add(gmp_init(2), 3), ' ', var_export(less(gmp_init(2), gmp_init(3)), true), "\n";
churn();
