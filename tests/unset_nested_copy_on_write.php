<?php
// unset() of a nested element separates every array on the path from its
// co-holders, so copies taken earlier keep their elements
$a = [["x" => 1, "y" => 2]]; $c = $a; unset($a[0]["x"]); var_dump(count($c[0]), count($a[0]));
function f() { $a = [["x" => 1, "y" => 2]]; $c = $a; unset($a[0]["x"]); var_dump(count($c[0]), count($a[0])); }
f();
class O { public $p = [["x" => 1, "y" => 2]]; }
$o = new O; $c = $o->p; unset($o->p[0]["x"]); var_dump(count($c[0]), count($o->p[0]));
class Holder {
    private $p = [["x" => 1, "y" => 2]];
    function go() { $c = $this->p; unset($this->p[0]["x"]); return [count($c[0]), count($this->p[0])]; }
}
var_dump((new Holder)->go());
class St { public static $a = ["k" => 1, "j" => 2]; public static $n = null; public static $m = [["x" => 1]]; }
$copy = St::$a; unset(St::$a["k"]); unset(St::$n["k"]);
$c2 = St::$m; unset(St::$m[0]["x"]);
var_dump($copy, St::$a, St::$n, $c2, St::$m);
$deep = [[[["x" => 1, "y" => 2]]]]; $dc = $deep; unset($deep[0][0][0]["x"]); var_dump(count($dc[0][0][0]), count($deep[0][0][0]));
$GLOBALS["gg"] = [["x" => 1, "y" => 2]]; $gc = $gg; unset($GLOBALS["gg"][0]["x"]); var_dump(count($gc[0]), count($gg[0]));

// a reference into the array is shared on purpose
$r1 = [["x" => 1, "y" => 2]]; $ref = &$r1[0]; unset($r1[0]["x"]); var_dump(count($ref));
$b = [["x" => 1]]; $rb = &$b; $cb = $b; unset($b[0]["x"]); var_dump(count($rb[0]), count($cb[0]));

// objects inside arrays are handles
$h = new stdClass; $h->p = ["x" => 1, "y" => 2]; $arr = [$h]; $ac = $arr; unset($arr[0]->p["x"]); var_dump(count($ac[0]->p));

// an unset through offsetGet works on a copy
class Bag implements ArrayAccess {
    public $d = ["in" => ["x" => 1, "y" => 2]];
    function offsetExists($k): bool { return isset($this->d[$k]); }
    function offsetGet($k): mixed { echo "get $k\n"; return $this->d[$k]; }
    function offsetSet($k, $v): void {}
    function offsetUnset($k): void { echo "unset $k\n"; }
}
$g = ["bag" => new Bag]; unset($g["bag"]["in"]); unset($g["bag"]["in"]["x"]); var_dump(count($g["bag"]->d["in"]));

// missing levels are not created
$m = ["a" => []]; unset($m["a"]["b"]["c"]); unset($m["z"]["y"]); var_dump($m);
