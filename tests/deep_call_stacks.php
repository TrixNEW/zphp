<?php
// recursion far deeper than a fixed-size stack would allow keeps working:
// pending operands in every frame, try blocks in every frame, argument
// bookkeeping for func_get_args and backtraces, generators and fibers

function depth($n) { return $n == 0 ? 0 : 1 + depth($n - 1); }
echo depth(20000), "\n";

function guarded($n) {
    try {
        return $n == 0 ? 0 : guarded($n - 1) + 1;
    } finally {
    }
}
echo guarded(5000), "\n";

function args($n, ...$rest) {
    if ($n == 0) return count(func_get_args());
    return args($n - 1, $n, $n * 2);
}
echo args(3000), "\n";

function trace($n, $label) {
    if ($n == 0) {
        $t = debug_backtrace();
        return count($t) . ':' . count($t[2000]['args']) . ':' . $t[2000]['args'][1];
    }
    return trace($n - 1, "L$n");
}
echo trace(2500, 'top'), "\n";

class Node {
    public $next;
    public $value;
    public function sum() { return $this->value + ($this->next ? $this->next->sum() : 0); }
}
$head = null;
for ($i = 1; $i <= 10000; $i++) { $n = new Node(); $n->value = $i; $n->next = $head; $head = $n; }
echo $head->sum(), "\n";

function nestedTry() {
    try { try { try { try { try { try { try { try { try { try {
        yield 1;
        yield 2;
    } finally { echo "f10 "; } } finally { echo "f9 "; } } finally { echo "f8 "; } } finally { echo "f7 "; } } finally { echo "f6 "; }
    } finally { echo "f5 "; } } finally { echo "f4 "; } } finally { echo "f3 "; } } finally { echo "f2 "; } } finally { echo "f1\n"; }
}
foreach (nestedTry() as $v) echo $v, ' ';

$closureDepth = function ($n) use (&$closureDepth) { return $n == 0 ? 'done' : $closureDepth($n - 1); };
echo $closureDepth(8000), "\n";

$fiber = new Fiber(function () {
    $inner = function ($n) use (&$inner) {
        if ($n == 0) return Fiber::suspend('deep');
        return $inner($n - 1);
    };
    return $inner(3000);
});
echo $fiber->start(), ' ';
$fiber->resume('back');
echo $fiber->getReturn(), "\n";

echo array_sum(array_map(fn($i) => depth(3000), [1, 2])), "\n";
