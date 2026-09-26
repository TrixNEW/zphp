<?php
// reading or writing a static property nothing declares is an Error, and so
// is naming a class that does not exist; isset, ?? and empty read null instead

trait Counts { public static $count = 0; }
class Base { public static $inherited = 'base'; }
class K extends Base {
    use Counts;
    public static $declared = null;
    public static $list = [];
    public static function probe() { return static::$nope; }
}

function attempt(string $label, callable $f): void
{
    try {
        var_dump($f());
    } catch (Error $e) {
        echo "$label: ", get_class($e), ': ', $e->getMessage(), "\n";
    }
}

attempt('read', fn() => K::$nope);
attempt('read unknown class', fn() => Missing::$x);
attempt('read through static', fn() => K::probe());
attempt('dynamic class', function () { $c = 'K'; return $c::$nope; });
attempt('dynamic name', function () { $p = 'nope'; return K::$$p; });
attempt('dynamic both', function () { $c = 'K'; $p = 'nope'; return $c::$$p; });
attempt('object class', fn() => (new K)::$nope);
attempt('write', function () { K::$nope = 1; });
attempt('write unknown class', function () { Missing::$x = 1; });
attempt('dynamic write', function () { $c = 'K'; $p = 'nope'; $c::$$p = 1; });
attempt('append', function () { K::$nope[] = 1; });
attempt('reference', function () { $r = &K::$nope; });
attempt('compound', function () { K::$nope .= 'x'; });

// declared ones, including null, inherited and trait-declared, still work
attempt('declared null', fn() => K::$declared);
attempt('inherited', fn() => K::$inherited);
K::$count++;
K::$list[] = 'a';
$c = 'K';
$p = 'list';
$c::$$p[] = 'b';
attempt('trait and list', fn() => [K::$count, K::$list]);

// the quiet forms
var_dump(isset(K::$nope), isset(K::$declared), isset(K::$inherited));
var_dump(isset(K::$list[0]), isset(K::$nope[0]), isset(K::$nope->x));
var_dump(K::$nope ?? 'fallback', K::$declared ?? 'null declared', K::$list[5] ?? 'no index');
var_dump(empty(K::$nope), empty(K::$inherited), empty(K::$list));
// a missing class is still an error
attempt('isset unknown class', fn() => isset(Missing::$x));
attempt('coalesce unknown class', fn() => Missing::$x ?? 'fallback');
attempt('empty unknown class', fn() => empty(Missing::$x));
$c = 'K';
$p = 'nope';
var_dump(isset($c::$nope), isset(K::$$p), isset($c::$$p), $c::$$p ?? 'dynamic fallback');

