<?php
// temporaries with no durable owner (array literals passed to natives, foreach
// subjects, generators in foreach) are released promptly
class Foo { public $n; function __construct($n) { $this->n = $n; } function __destruct() { echo "d{$this->n} "; } }
class Counted { public static $destroyed = 0; function __destruct() { self::$destroyed++; } }
function g() { yield new Foo(1); yield 2; }
function cg() { yield new Counted; yield 2; }
echo "gen: "; foreach (g() as $v) {} unset($v); echo "after\n";
echo "gen-return: "; function h() { $keep = new Foo(2); yield 1; return 'done'; } $it = h(); foreach ($it as $_) {} echo $it->getReturn(), " "; unset($it); echo "after\n";
echo "literal-foreach: "; foreach ([new Foo(3), 2] as $v) {} unset($v); echo "after\n";
echo "literal-arg: "; $c = count([new Foo(4), 2]); echo $c, " "; echo "after\n";
$before = memory_get_usage();
for ($i = 0; $i < 20000; $i++) { $x = count([new Counted, 2]); foreach ([new Counted, 2] as $v) {} foreach (cg() as $v) {} }
unset($v);
echo "loop: ", Counted::$destroyed, " ", (memory_get_usage() - $before < 4 * 1024 * 1024) ? "bounded" : "unbounded", "\n";
echo "end\n";
