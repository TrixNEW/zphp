<?php
// final on promoted constructor properties (php 8.5)
class A { function __construct(final public int $x = 1, final int $y = 2, public final readonly int $z = 3, final private int $p = 4) {} }
$r = new ReflectionProperty("A","y"); var_dump((new A)->y, $r->isFinal(), $r->isPublic(), $r->isPromoted(), (new ReflectionProperty("A","z"))->isFinal(), (new ReflectionProperty("A","p"))->isFinal());
echo implode(" ", Reflection::getModifierNames($r->getModifiers())), "\n";
