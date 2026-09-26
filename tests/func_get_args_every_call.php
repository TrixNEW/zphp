<?php
// every kind of call keeps its own arguments for func_get_args and traces:
// constructors, methods called in a hot loop, and arguments past the declared
// ones, never the values of an earlier call that used the same frame slot

function other($a, $b, $c) { return func_get_args(); }
class A { public function __construct($x) { var_dump(func_get_args(), func_num_args()); } }
other(10, 20, 30);
new A(1, 2, 3);

class B { public function __construct() { var_dump(func_get_args()); throw new Exception("b"); } }
try { new B(5, 6); } catch (Exception $e) { echo json_encode($e->getTrace()[0]['args']), "\n"; }

class Counter {
    public function add($n) { return array_sum(func_get_args()); }
    public function fail($n) { throw new Exception("fail"); }
}
$c = new Counter();
$total = 0;
for ($i = 0; $i < 1000; $i++) $total += $c->add($i, 1, 1);
echo $total, "\n";
for ($i = 0; $i < 3; $i++) {
    try { $c->fail($i, 'extra'); } catch (Exception $e) { echo json_encode($e->getTrace()[0]['args']), "\n"; }
}
