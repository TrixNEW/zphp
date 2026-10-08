<?php
// php 8.5's clone($object, $withProperties): __clone runs first, then each
// property is assigned with the caller's scope. a readonly property can be
// written again from a scope its set visibility allows (the wither pattern)
final class Point {
    public function __construct(
        public readonly int $x = 1,
        public private(set) string $label = "origin",
        public $note = null,
        private $secret = 0,
    ) {}
    public function withX(int $x): static { return clone($this, ['x' => $x]); }
    public function withSecret($v): static { return clone($this, ['secret' => $v, 'label' => 'inside']); }
    public function secret() { return $this->secret; }
    public function __clone() { echo "__clone note=", var_export($this->note, true), "\n"; }
}

$p = new Point;
$q = $p->withX(5);
echo $q->x, " ", $p->x, "\n";
$s = $p->withSecret(3);
echo $s->secret(), " ", $s->label, " ", $p->secret(), " ", $p->label, "\n";

$t = function ($f) {
    try {
        $r = $f();
        echo is_object($r) ? json_encode(get_object_vars($r)) : var_export($r, true), "\n";
    } catch (Throwable $e) {
        echo get_class($e), ": ", $e->getMessage(), "\n";
    }
};
$t(fn() => clone($p, ['x' => 9]));
$t(fn() => clone($p, ['label' => 'z']));
$t(fn() => clone($p, ['note' => 7]));
$t(fn() => clone($p, ['note' => 7, ]));
$t(fn() => clone($p, []));
$t(fn() => (clone($p))->note);
$t(fn() => clone(new stdClass, ['a' => 1, 'b' => [2]]));
$t(fn() => clone 5);

// arguments are evaluated before the clone is made
function arg($v) { echo "arg $v\n"; return $v; }
$t(fn() => clone($p, ['note' => arg('n')]));

// set hooks and typed properties apply as for any write
class Temp {
    public int $celsius = 0;
    public float $f { set(float $v) { echo "hook $v\n"; $this->f = $v; } get => $this->f; }
}
$t(fn() => (clone(new Temp, ['celsius' => '21', 'f' => 1.5]))->celsius);
$t(fn() => clone(new Temp, ['celsius' => 'hot']));
