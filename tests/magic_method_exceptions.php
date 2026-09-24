<?php
class M implements ArrayAccess, IteratorAggregate, Countable {
    public function __get($n) { throw new LogicException("get"); }
    public function __set($n, $v) { throw new LogicException("set"); }
    public function __isset($n) { throw new LogicException("isset"); }
    public function __unset($n) { throw new LogicException("unset"); }
    public function __call($n, $a) { throw new LogicException("call"); }
    public static function __callStatic($n, $a) { throw new LogicException("callStatic"); }
    public function __toString(): string { throw new LogicException("toString"); }
    public function __invoke() { throw new LogicException("invoke"); }
    public function offsetGet($o): mixed { throw new LogicException("offsetGet"); }
    public function offsetSet($o, $v): void { throw new LogicException("offsetSet"); }
    public function offsetExists($o): bool { throw new LogicException("offsetExists"); }
    public function offsetUnset($o): void { throw new LogicException("offsetUnset"); }
    public function getIterator(): Iterator { throw new LogicException("getIterator"); }
    public function count(): int { throw new LogicException("count"); }
    public function __clone() { throw new LogicException("clone"); }
}
$m = new M;
$cases = [
    'get' => function () use ($m) { $x = $m->a; },
    'set' => function () use ($m) { $m->a = 1; },
    'isset' => function () use ($m) { isset($m->a); },
    'unset' => function () use ($m) { unset($m->a); },
    'call' => function () use ($m) { $m->nope(); },
    'callStatic' => function () { M::nope(); },
    'toString' => function () use ($m) { $s = "x" . $m; },
    'invoke' => function () use ($m) { $m(); },
    'offsetGet' => function () use ($m) { $x = $m[1]; },
    'offsetSet' => function () use ($m) { $m[1] = 2; },
    'offsetExists' => function () use ($m) { isset($m[1]); },
    'offsetUnset' => function () use ($m) { unset($m[1]); },
    'getIterator' => function () use ($m) { foreach ($m as $v) {} },
    'count' => function () use ($m) { count($m); },
    'clone' => function () use ($m) { clone $m; },
];
foreach ($cases as $name => $case) {
    try {
        $case();
        echo "$name: no exception\n";
    } catch (LogicException $e) {
        echo "$name: caught ", $e->getMessage(), "\n";
    }
}
echo "end\n";
