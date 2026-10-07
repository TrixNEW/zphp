<?php
// reflection metadata of static properties, and modifier names in php's order
class P { private static $a = 1; protected static int $b = 2; public private(set) static ?string $c = null; public static $d = []; }
foreach ((new ReflectionClass('P'))->getProperties() as $p) {
    echo $p->getName(), " ", implode(" ", Reflection::getModifierNames($p->getModifiers())), " ", $p->getModifiers(),
        " priv=", var_export($p->isPrivate(), true), " pset=", var_export($p->isPrivateSet(), true), " type=", $p->getType() ?? "-", "\n";
}
$r = new ReflectionProperty('P', 'a');
var_dump($r->isPrivate(), $r->isStatic(), $r->getValue());
foreach ([1, 2, 4, 16, 32, 64, 128, 0x800, 0x1000, 0x20, 0x10000, 0x11, 0x14, 0x841, 0x1031, 0x10011, 0x200, 0x100, 0xFFFFF] as $m) {
    echo dechex($m), " ", implode(",", Reflection::getModifierNames($m)), "\n";
}
