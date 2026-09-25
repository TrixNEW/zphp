<?php
// closure parameters enforce their declared types like named functions
function report(callable $call): void
{
    try {
        var_dump($call());
    } catch (TypeError $e) {
        echo get_class($e), ': ', substr($e->getMessage(), strpos($e->getMessage(), '(): ') + 4), "\n";
    }
}

$int = function (int $n) { return $n * 2; };
$str = fn (string $s): string => strtoupper($s);
$obj = static function (object $o) { return get_class($o); };
$many = function (int ...$n) { return array_sum($n); };
$strict = function (?array $a = null) { return $a; };

report(fn () => $int(21));
report(fn () => $int('21'));
report(fn () => $int('abc'));
report(fn () => $int(new stdClass));
report(fn () => $str(5));
report(fn () => $str([]));
report(fn () => $obj(new ArrayObject));
report(fn () => $obj(STDIN));
report(fn () => $many(1, 2, 3));
report(fn () => $many(1, 'x'));
report(fn () => $strict());
report(fn () => $strict('no'));
report(fn () => (function (float $f) { return $f; })(3));
report(fn () => (function (bool $b) { return $b; })([]));
report(fn () => array_map(fn (int $x) => $x + 1, [1, 2, 'three']));

// methods, variadics, and user code called back from natives check too
class Sorter
{
    public function cmp(int $a, int $b): int
    {
        return $a <=> $b;
    }

    public static function twice(int $x): int
    {
        return $x * 2;
    }

    public function __invoke(float $x): float
    {
        return $x;
    }
}
function total(int ...$n): int
{
    return array_sum($n);
}
$words = ['q', 'r'];
$nums = ['3', '1', '2'];
report(fn () => (new Sorter)->cmp('x', 1));
report(fn () => (new Sorter)->cmp('2', 1));
report(fn () => usort($words, [new Sorter, 'cmp']));
report(fn () => usort($nums, [new Sorter, 'cmp']) ? $nums : null);
report(fn () => array_map('Sorter::twice', ['4', 'x']));
report(fn () => array_map([Sorter::class, 'twice'], [5]));
report(fn () => array_map(new Sorter, [1, '2.5']));
report(fn () => (new Sorter)('nope'));
report(fn () => total(1, '2', 3));
report(fn () => total(1, 'two'));
report(fn () => call_user_func('total', 4, []));
report(fn () => call_user_func_array([new Sorter, 'cmp'], ['a' => 'w', 'b' => 1]));
