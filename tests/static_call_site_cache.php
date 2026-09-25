<?php
// one static call site reached through several late static binding classes
class Base {
    public static function name() { return 'base'; }
    public static function via() { return static::name(); }
    public static function viaSelf() { return self::name(); }
    public function inst() { return static::name(); }
    public function fwd() { return static::who(); }
    public function who() { return get_class($this); }
}
class Child extends Base {
    public static function name() { return 'child'; }
}
class Grand extends Child {
    public static function name() { return 'grand:' . parent::name(); }
}

$out = [];
for ($i = 0; $i < 3; $i++) {
    foreach (['Base', 'Child', 'Grand'] as $c) {
        $out[] = $c::via();
        $out[] = $c::viaSelf();
        $out[] = (new $c)->inst();
        $out[] = (new $c)->fwd();
    }
}
echo implode(',', $out), "\n";

// a site whose target is declared after the site first runs
function callLater($c) { return $c::late(); }
class Early { public static function late() { return 'early'; } }
echo callLater('Early'), "\n";
class Later extends Early { public static function late() { return 'later'; } }
echo callLater('Later'), ' ', callLater('Early'), "\n";

// static site calling a non-static method forwards $this
class Scoped {
    public $v = 'scoped';
    public function read() { return $this->v; }
    public function viaStatic() { return static::read(); }
}
$s = new Scoped;
echo $s->viaStatic(), $s->viaStatic(), "\n";

// argument count errors still raise from a cached site
class Strict { public static function need($a, $b) { return $a + $b; } }
function callStrict($n) { return $n === 2 ? Strict::need(1, 2) : Strict::need(1); }
echo callStrict(2), callStrict(2), "\n";
try { callStrict(1); } catch (ArgumentCountError $e) { echo get_class($e), "\n"; }

// method resolution across many classes and methods from one loop
class Pa { public function m1() { return 1; } public function m2() { return 2; } public function m3() { return 3; } }
class Pb extends Pa { public function m2() { return 20; } }
$sum = 0;
foreach ([new Pa, new Pb, new Pa, new Pb] as $o) {
    foreach (['m1', 'm2', 'm3', 'M2'] as $m) $sum += $o->$m();
}
echo $sum, "\n";

// private property resolution from the declaring scope
class PrivA { private $x = 'a'; public function getA() { return $this->x; } }
class PrivB extends PrivA { private $x = 'b'; public $y = 'y'; public function getB() { return $this->x . $this->y; } }
$b = new PrivB;
echo $b->getA(), $b->getB(), "\n";

// || chains produce booleans
$chars = [' ', "\n", 'q', '0', '', "\t"];
$r = [];
foreach ($chars as $ch) $r[] = var_export(' ' === $ch || "\n" === $ch || "\t" === $ch, true);
foreach ($chars as $ch) $r[] = var_export($ch || 0, true);
echo implode(',', $r), "\n";
