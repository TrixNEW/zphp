<?php
// class, interface, trait, and method names are case-insensitive. php reports
// the spelling of the declaration whatever spelling the code used

interface Shape { function area(): float; }
trait Named { function label(): string { return static::class; } }

class Square implements shape {
    use named;
    const SIDES = 4;
    public static $made = 0;
    function __construct(public float $side = 2) { self::$made++; }
    function area(): float { return $this->side ** 2; }
    static function unit(): static { return new static(1); }
}
class Cube extends SQUARE {}

echo "-- constants and static members\n";
var_dump(SQUARE::SIDES, square::$made, sQuArE::unit()->area());
var_dump(constant("square::SIDES"), defined("SQUARE::SIDES"));

echo "-- instantiation\n";
$s = new square(3);
var_dump(get_class($s), get_class(new CUBE), $s::class);
$name = "cube";
var_dump(get_class(new $name), (new $name) instanceof SQUARE);

echo "-- instanceof and relationships\n";
var_dump($s instanceof SQUARE, $s instanceof SHAPE, new Cube instanceof square);
var_dump(is_a($s, "SHAPE"), is_a("cube", "Square", true), is_subclass_of("CUBE", "square"));
var_dump(get_parent_class(new Cube), get_parent_class("cube"));
var_dump(class_implements("CUBE"), class_uses("SQUARE"));
var_dump(class_exists("CUBE"), interface_exists("shape"), trait_exists("NAMED"));

echo "-- methods\n";
var_dump($s->AREA(), $s->Label(), CUBE::UNIT()->label());
var_dump(method_exists("CUBE", "AREA"), method_exists($s, "label"));
var_dump(get_class_methods("cube") == get_class_methods("Cube"));

echo "-- callables\n";
var_dump(call_user_func("SQUARE::unit")->area());
var_dump(call_user_func(["cube", "UNIT"])->area());
var_dump(call_user_func_array([$s, "AREA"], []));
var_dump(is_callable("square::UNIT"), is_callable(["CUBE", "unit"]), is_callable("square::nope"));
var_dump(array_map("SQUARE::unit", [1])[0]->area());
var_dump(Closure::fromCallable("square::unit")()->area());
var_dump(SQUARE::unit(...)()->area());

echo "-- reflection\n";
$r = new ReflectionClass("cube");
var_dump($r->getName(), $r->getParentClass()->getName());
var_dump($r->isSubclassOf("SQUARE"), $r->implementsInterface("SHAPE"), $r->isInstance(new CUBE));
$m = new ReflectionMethod("SQUARE", "AREA");
var_dump($m->getName(), $m->class);
var_dump((new ReflectionMethod("square::unit"))->invoke(null)->area());

echo "-- exceptions\n";
try {
    throw new RUNTIMEEXCEPTION("boom");
} catch (exception $e) {
    var_dump(get_class($e), $e instanceof THROWABLE);
}

echo "-- serialization\n";
var_dump(serialize(new CUBE(2)));
var_dump(get_class(unserialize('O:4:"CUBE":1:{s:4:"side";d:2;}')));

echo "-- builtin classes\n";
var_dump(get_class(new arrayobject([])), new ARRAYOBJECT([]) instanceof countable);
var_dump(PDO::ATTR_ERRMODE === pdo::ATTR_ERRMODE);
