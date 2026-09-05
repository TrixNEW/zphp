<?php
// extract() assigns like `$name = $value`: the variable takes its own
// reference, an overwritten value is released, and the extracted object
// survives the source array and any cycle collection
class Foo {
    public $n;
    public $self;
    function __construct($n) { $this->n = $n; }
    function __destruct() { echo "destruct {$this->n}\n"; }
}
function scope() {
    $data = ['one' => new Foo(1), 'two' => new Foo(2)];
    $one = new Foo(10);
    $count = extract($data);
    echo "extract: ", $count, "\n";
    unset($data);
    echo "kept: ", $one->n, " ", $two->n, "\n";
    $cycle = new Foo(3);
    $cycle->self = $cycle;
    extract(['cycle' => new Foo(4)]);
    gc_collect_cycles();
    echo "cycle: ", $cycle->n, "\n";
    extract(['one' => new Foo(11)], EXTR_SKIP);
    extract(['two' => new Foo(12)], EXTR_OVERWRITE);
    echo "skip: ", $one->n, " overwrite: ", $two->n, "\n";
    unset($one);
    unset($two);
    unset($cycle);
    echo "leaving\n";
}
scope();
$top = ['g' => new Foo(6)];
extract($top);
unset($top);
echo "global: ", $g->n, " ", $GLOBALS['g']->n, "\n";
unset($g);
echo "end\n";
