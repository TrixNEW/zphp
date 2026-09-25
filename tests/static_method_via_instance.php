<?php
// static methods reached through an instance, $this or static:: bind their
// arguments from the first parameter slot, with and without defaults

class Registry
{
    private static $items = [];
    public static function none() { return 'none'; }
    public static function one($a) { return "one:$a"; }
    public static function withDefault($a, $b = 'dflt') { return "two:$a:$b"; }
    protected static function hidden($x) { return "hidden:$x"; }
    public static function add($key, $value) { static::$items[$key] = $value; return count(static::$items); }
    public function viaThis($x) { return $this->hidden($x) . '|' . $this->one($x) . '|' . static::withDefault($x); }
}

$r = new Registry();
for ($i = 0; $i < 3; $i++) {
    echo $r->none(), ' ', $r->one($i), ' ', $r->withDefault($i), ' ', $r->withDefault($i, 'x'), ' ', $r->viaThis($i), ' ', $r->add("k$i", $i), "\n";
}
function useCallback(Registry $r) { return array_map([$r, 'one'], [7, 8]); }
echo implode(',', useCallback($r)), "\n";
$closure = function () { return $this->one('bound'); };
echo Closure::bind($closure, $r, Registry::class)(), "\n";
