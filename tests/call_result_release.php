<?php
// call results (callbacks invoked by natives, magic methods, iterators) hold
// no extra reference: objects they return are destructed as soon as the last
// holder drops them
class Foo { public $n; function __construct($n) { $this->n = $n; } function __destruct() { echo "d{$this->n} "; } }
class Bag implements ArrayAccess, IteratorAggregate {
    function offsetGet($k): mixed { return new Foo($k); }
    function offsetSet($k, $v): void {}
    function offsetExists($k): bool { return true; }
    function offsetUnset($k): void {}
    function __get($n) { return new Foo(100); }
    function getIterator(): Iterator { return new ArrayIterator([new Foo(200)]); }
    function __call($m, $a) { return new Foo(300); }
}
function mk($n) { return new Foo($n); }
echo "map: "; $r = array_map('mk', [1, 2]); unset($r); echo "after\n";
echo "map-closure: "; $r = array_map(fn($n) => new Foo($n), [3, 4]); unset($r); echo "after\n";
echo "cuf: "; $x = call_user_func('mk', 5); unset($x); echo "after\n";
echo "cufa: "; $x = call_user_func_array('mk', [6]); unset($x); echo "after\n";
echo "reduce: "; $x = array_reduce([1, 2], fn($c, $n) => new Foo($n + 10), null); unset($x); echo "after\n";
echo "filter: "; $keep = [new Foo(20)]; $x = array_filter($keep, fn($o) => true); unset($x, $keep); echo "after\n";
echo "walk: "; $a = [1]; array_walk($a, function (&$v) { $v = new Foo(30); }); unset($a); echo "after\n";
echo "usort: "; $a = [new Foo(9), new Foo(8)]; usort($a, fn($x, $y) => $x->n <=> $y->n); unset($a); echo "after\n";
$b = new Bag;
echo "offsetGet: "; $x = $b[1]; unset($x); echo "after\n";
echo "__get: "; $x = $b->zz; unset($x); echo "after\n";
echo "__call: "; $x = $b->foo(); unset($x); echo "after\n";
echo "iter: "; foreach ($b as $v) {} unset($v); echo "after\n";
echo "invoke: "; class Inv { function __invoke() { return new Foo(500); } } $i = new Inv; $x = $i(); unset($x); echo "after\n";
echo "string results: ";
$s = array_map(fn($n) => str_repeat('x', $n), [1, 2, 3]);
echo implode(',', $s), " ", strlen(call_user_func('str_repeat', 'y', 4)), "\n";
echo "end\n";
