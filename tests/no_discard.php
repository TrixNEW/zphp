<?php
// php 8.5's #[\NoDiscard]: a call whose result the statement throws away
// warns at the caller's line; (void), @, and using the value don't
#[\NoDiscard] function f() { return 1; }
#[\NoDiscard("check the result")] function g() { return 2; }
class C {
    #[\NoDiscard] public function m() { return 3; }
    #[\NoDiscard] public static function s() { return 4; }
}
f();
g();
(new C)->m();
C::s();
(void) f();
$x = f();
echo f(), "\n";
f() + 1;
$fn = 'f';
$fn();
array_map('f', [1]);
$c = f(...);
$c();
if (f()) {}
@f();
try { f(); } catch (Throwable $e) {}
$closure = #[\NoDiscard] fn() => 5;
$closure();
(void) $closure();
for ((void) f(); false; ) {}

// a user error handler sees the warning at the call's line
set_error_handler(function ($no, $str, $file, $line) {
    echo "handler: $str (line $line)\n";
    return true;
});
f();
restore_error_handler();

$r = new ReflectionFunction('f');
var_dump(count($r->getAttributes()), $r->getAttributes()[0]->getName());
echo "end\n";
