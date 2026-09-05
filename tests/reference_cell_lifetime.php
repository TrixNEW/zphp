<?php
// One shared cell must be trial-decremented once, not once per array entry.
function shared_cell_cycle() {
    $a = [];
    $a['one'] = &$a;
    $a['two'] = &$a;
    unset($a);
    echo gc_collect_cycles(), "\n";
}
shared_cell_cycle();
// An external reference keeps both the cell and its graph alive.
$a = [];
$a['self'] = &$a;
$keep = function () use (&$a) { return $a['n']; };
$a['n'] = 17;
unset($a);
gc_collect_cycles();
echo $keep(), "\n";
unset($keep);
echo gc_collect_cycles(), "\n";
// Recycled cells must not inherit array/property/capture index targets.
class RefCellLifetimeFoo { public $n = 0; }
function ref_cell_replace(&$v, $n) { $v = new RefCellLifetimeFoo; $v->n = $n; }
for ($i = 0; $i < 2000; $i++) {
    $x = [1];
    $r = &$x;
    unset($r, $x);
    ref_cell_replace($v, $i);
    if ($v->n !== $i) echo "bad value\n";
    unset($v);
}
echo "recycled\n";
// Saved generator/fiber frames retain their reference binders across suspension.
function ref_cell_generator() {
    $x = 4;
    $r = &$x;
    yield function () use (&$r) { return ++$r; };
    yield $x;
}
$g = ref_cell_generator();
$f = $g->current();
echo $f(), "\n";
$g->next();
echo $g->current(), "\n";
unset($g, $f);
$fiber = new Fiber(function () {
    $v = 8;
    $r = &$v;
    Fiber::suspend($r);
    ++$r;
    return $v;
});
echo $fiber->start(), "\n";
$fiber->resume();
echo $fiber->getReturn(), "\n";
unset($fiber);
