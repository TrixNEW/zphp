<?php
// #[\Deprecated] warns E_USER_DEPRECATED at each use: functions and methods
// (php 8.4) on every call, constants and enum cases (8.5) on every read
set_error_handler(function ($no, $str, $file, $line) { echo "[$no] $str (line $line)\n"; return true; });
#[\Deprecated] function a() { return 1; }
#[\Deprecated("use b2()")] function b() {}
#[\Deprecated(since: "2.0")] function c() {}
#[\Deprecated("use d2()", "1.5")] function d() {}
#[\Deprecated(message: "", since: "")] function e() {}
class K {
    #[\Deprecated] public function m() {}
    #[\Deprecated("x")] public static function s() {}
    #[\Deprecated] const C = 1;
    #[\Deprecated("y", since: "3")] const D = 2;
}
enum E { #[\Deprecated] case A; }
#[\Deprecated] const G = 3;
a(); b(); c(); d(); e();
$x = a();
(new K)->m(); K::s();
echo K::C, K::D, "\n";
var_dump(E::A);
echo G, "\n";
$f = 'a'; $f();
array_map('a', [1]);
$cl = #[\Deprecated] function() {}; $cl();
#[\Deprecated] function gen() { yield 1; } gen();
echo constant('G'), constant('K::C'), "\n";

// inherited constants name the declaring class
class Base { #[\Deprecated("gone")] const OLD = 9; }
class Child extends Base {}
echo Child::OLD, "\n";

// reflection still describes the attribute
$m = new ReflectionMethod('K', 'm');
var_dump($m->isDeprecated(), count($m->getAttributes()));
