<?php
// ReflectionClass modifiers use php 8's bits (final 32, explicit abstract 64,
// readonly 65536); enums and Closure are final; an interface is abstract
// exactly when it has methods
final class F {} abstract class A {} readonly class R {} final readonly class FR {}
interface I {} trait T {} enum E {} class C {}
interface I1 { function m(); } interface I2 extends I1 {} interface I3 { const X = 1; }
foreach (["F", "A", "R", "FR", "I", "T", "E", "C", "Closure", "Generator", "Directory", "I1", "I2", "I3", "Countable", "Traversable", "Stringable"] as $c) {
    $r = new ReflectionClass($c);
    echo $c, " ", $r->getModifiers(), " ", implode(",", Reflection::getModifierNames($r->getModifiers())),
        " final=", var_export($r->isFinal(), true), " abstract=", var_export($r->isAbstract(), true),
        " instantiable=", var_export($r->isInstantiable(), true), "\n";
}

// a constructor that isn't public makes a class not instantiable
class S { private function __construct() {} } class S2 extends S {}
class P { protected function __construct() {} } class Q extends P { public function __construct() {} }
foreach (["S", "S2", "P", "Q"] as $c) echo $c, " ", var_export((new ReflectionClass($c))->isInstantiable(), true), "\n";
