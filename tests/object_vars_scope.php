<?php
// get_object_vars sees what the calling scope sees: a bound closure takes
// its bound class's view, an unscoped one sees public properties only

class A { protected $p = 1; private $q = 2; public $r = 3; }
$f = function () { return implode(',', array_keys(get_object_vars($this))); };
echo Closure::bind($f, new A, A::class)(), "\n";
echo $f->call(new A), "\n";
echo Closure::bind($f, new A, null)(), "\n";
echo Closure::bind(fn() => implode(',', array_keys(get_object_vars($this))), new A, A::class)(), "\n";

class B { private $x = 1; public function f() { return function () { return implode(',', array_keys(get_object_vars($this))); }; } }
class C extends B { protected $y = 2; public $z = 3; private $w = 4; }
echo (new C)->f()(), "\n";
echo Closure::bind(function () { return implode(',', array_keys(get_object_vars($this))); }, new C, C::class)(), "\n";
function outside(object $o) { return implode(',', array_keys(get_object_vars($o))); }
echo outside(new C), "\n";
$static = static function (object $o) { return implode(',', array_keys(get_object_vars($o))); };
echo Closure::bind($static, null, B::class)(new C), "\n";
