<?php
// a default that names an undefined constant or class throws when a call
// needs it, located at the function; reflection lists the parameter and only
// throws when asked for the value
namespace App;

class K { const A = 1; }
function f1($x = NOPE) { return $x; }
function f2($x = Missing::A) { return $x; }
function f3($x = K::NOPE) { return $x; }
function f4($x = [K::A, NOPE2]) { return $x; }
function f5($x = PHP_INT_SIZE) { return $x; }
function f6($x = K::A, $y = NOPE3) { return [$x, $y]; }

foreach (['App\f1', 'App\f2', 'App\f3', 'App\f4', 'App\f5'] as $f) {
    try {
        var_dump($f());
    } catch (\Error $e) {
        echo get_class($e), ': ', $e->getMessage(), ' @', $e->getLine(), "\n";
    }
}
var_dump(f1('given'), f2(2), f6(5, 6));

$params = (new \ReflectionFunction('App\f6'))->getParameters();
echo count($params), "\n";
var_dump($params[0]->getDefaultValue());
try {
    $params[1]->getDefaultValue();
} catch (\Error $e) {
    echo get_class($e), ': ', $e->getMessage(), "\n";
}
const NOPE3 = 'late';
var_dump($params[1]->getDefaultValue(), f6());
