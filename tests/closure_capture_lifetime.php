<?php
// closure captures live exactly as long as the closure instance
class Foo { public $n; function __construct($n) { $this->n = $n; } function __destruct() { echo "d{$this->n} "; } }
echo "capture: "; $o = new Foo(1); $f = function () use ($o) { return $o->n; }; unset($o); echo $f(), " "; unset($f); echo "after\n";
echo "arrow: "; $o = new Foo(2); $f = fn() => $o->n; unset($o); echo $f(), " "; unset($f); echo "after\n";
echo "bound: "; class Ctx { public $v = 7; } $g = function () { return $this->v; }; $h = $g->bindTo(new Ctx); echo $h(), " "; unset($g, $h); echo "after\n";
echo "call: "; $g = function ($x) { return $this->v + $x; }; echo $g->call(new Ctx, 1), " "; unset($g); echo "after\n";
function counter() { $n = 0; return function () use (&$n) { return ++$n; }; }
$c = counter(); echo "byref: ", $c(), $c(), $c(), "\n";
function memoize(callable $fn): callable {
    $cache = [];
    return function (...$args) use ($fn, &$cache) {
        $key = implode(':', $args);
        if (!array_key_exists($key, $cache)) $cache[$key] = $fn(...$args);
        return $cache[$key];
    };
}
$fact = memoize(function (int $n) use (&$fact): int { return $n <= 1 ? 1 : $n * $fact($n - 1); });
echo "memo: ", $fact(5), " ", $fact(10), "\n";
function reassign() { $v = [1]; $f = function () use (&$v) { $v = array_merge($v, [count($v) + 1]); return count($v); }; return $f; }
$r = reassign(); echo "reassign: ", $r(), $r(), $r(), "\n";
echo "static: "; $mk = function () { return function () { static $n = 0; return ++$n; }; }; $a = $mk(); $b = $mk(); echo $a(), $a(), $b(), "\n";
$before = memory_get_usage();
for ($i = 0; $i < 20000; $i++) { $p = str_repeat(' ', 512); $f = function ($x) use ($p) { return strlen($p) + $x; }; $f(1); unset($f, $p); }
echo "loop: ", (memory_get_usage() - $before < 4 * 1024 * 1024) ? "bounded" : "unbounded", "\n";
echo "end\n";
