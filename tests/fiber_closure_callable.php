<?php
// a fiber owns its callable; suspended fibers may be abandoned
$x = 5;
$f = new Fiber(function () use ($x) { Fiber::suspend($x); return $x * 2; });
echo $f->start(), "\n";
$g = new Fiber(function () use ($x) { $inner = new Fiber(function () use ($x) { Fiber::suspend($x + 1); }); echo $inner->start(), "\n"; return 1; });
$g->start();
echo $g->isTerminated() ? "done" : "suspended", "\n";
$r = 0;
$h = new Fiber(function () use (&$r) { $r = 9; });
$h->start();
echo $r, "\n";
