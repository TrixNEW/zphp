<?php
class Base { public function describe(int $n): string { return "base $n"; } }
class Child extends Base {
    public function describe(int $n): string {
        $f = fn(): string => parent::describe($n);
        $g = function () use ($n) { return parent::describe($n + 1); };
        return $f() . ' | ' . $g() . ' | ' . (new Helper)->run(fn() => parent::describe(9));
    }
}
class Helper { public function run(callable $c) { return $c(); } }
echo (new Child)->describe(1), "\n";

class StaticBase { public static function name(): string { return 'static base'; } }
class StaticChild extends StaticBase {
    public static function name(): string { return 'child'; }
    public static function viaClosure(): string { return (new Helper)->run(static fn() => parent::name()); }
}
echo StaticChild::viaClosure(), "\n";

class Other extends Base { public function describe(int $n): string { return "other $n"; } }
$bound = Closure::bind(function () { return parent::describe(5); }, new Other, Other::class);
echo (new Helper)->run($bound), "\n";
