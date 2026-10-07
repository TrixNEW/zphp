<?php
// foreach and destructuring may write into static properties and variable variables
class A { public static $p = 0; public static $q = 0; public static $r = 0; }
foreach ([5, 6] as A::$p) {}
foreach (['k' => 1] as A::$q => $v) {}
[A::$r, $x] = [7, 8];
['a' => A::$p] = ['a' => 9];
$n = "q"; $c = "A";
[A::$$n] = [10];
[$c::$r] = [11];
$vv = "dyn"; [$$vv] = [12];
foreach ([13] as $$vv) {}
var_dump(A::$p, A::$q, A::$r, $x, $dyn);
