<?php
// static properties follow their visibility and set visibility: errors name the
// class as written for access and the declaring class for set visibility
class P { private static $x = 1; protected static $y = 2; public protected(set) static int $z = 3; public static $p = 4;
  static function px() { return static::$x; } }
class C extends P { static function cy() { return static::$y . self::$y; } static function cx() { return self::$x; } static function cz() { static::$z = 30; return self::$z; } }
$t = function($f) { try { echo $f(), "\n"; } catch (Error $e) { echo get_class($e), ": ", $e->getMessage(), "\n"; } };
$t(fn() => C::$x);
$t(fn() => C::$y);
$t(fn() => C::cy());
$t(fn() => C::cx());
$t(fn() => P::px());
$t(fn() => C::px());
$t(fn() => C::cz());
$t(function() { C::$z = 5; });
$t(function() { $r = &P::$y; });
$t(fn() => isset(P::$x) ? "set" : "not set");
$t(fn() => P::$x ?? "dflt");
$t(fn() => C::$p);
$t(fn() => (function() { return static::$x; })->bindTo(null, P::class)());
$n = "x"; $t(fn() => P::$$n);
$c = "P"; $t(fn() => $c::$y);

// asymmetric visibility on static properties (php 8.5)
class S {
    public private(set) static int $n = 2;
    protected(set) static ?string $s = null;
    public private(set) static array $a = [];
    public static function bump() { self::$n++; static::$s = "x"; self::$a[] = 1; }
}
class SChild extends S {
    public static function setS() { static::$s = "from child"; }
    public static function setN() { static::$n = 9; }
}
S::bump();
echo S::$n, " ", S::$s, " ", count(S::$a), "\n";
SChild::setS();
echo S::$s, "\n";
$t(fn() => SChild::setN(), 'child');
$t(function() { S::$n = 1; });
$t(function() { S::$n += 1; });
$t(function() { S::$n++; });
$t(function() { --S::$n; });
$t(function() { S::$a[] = 1; });
$t(function() { S::$a['k'] = 1; });
$t(function() { unset(S::$a['k']); });
$t(function() { $r = &S::$n; });
$t(function() { foreach ([1] as S::$n) {} });
$t(function() { [S::$n] = [1]; });
echo S::$n, "\n";
$p = new ReflectionProperty(S::class, 'n');
var_dump($p->isPrivateSet(), $p->isProtectedSet(), $p->isStatic(), (new ReflectionProperty(S::class, 's'))->isProtectedSet());
