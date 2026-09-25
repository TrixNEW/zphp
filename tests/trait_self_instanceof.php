<?php
trait Same {
    public function same(object $o): bool { return $o instanceof self; }
    public function sameStatic(object $o): bool { return $o instanceof static; }
    public static function make(): self { return new self(); }
    public function cls(): string { return self::class; }
}
class A { use Same; }
class B { use Same; }
class A2 extends A {}
$a = new A;
var_dump($a->same(new A), $a->same(new B), $a->same(new A2), (new A2)->sameStatic(new A), get_class(A::make()), $a->cls(), (new B)->cls());
trait Lineage {
    public function isParentKind(object $o): bool { return $o instanceof parent; }
}
class Root {}
class Leaf extends Root { use Lineage; }
var_dump((new A)->sameStatic(new A2), (new Leaf)->isParentKind(new Root), (new Leaf)->isParentKind(new A));
