<?php
// creating an undeclared property is deprecated (php 8.2) unless the class is
// stdClass or carries #[\AllowDynamicProperties]; readonly classes and some
// internal classes refuse with an Error
set_error_handler(function ($no, $str, $file, $line) { echo "[$no] $str (line $line)\n"; return true; });
class P { public $declared; }
$p = new P;
$p->declared = 1;
$p->dyn = 1;
$p->dyn = 2;
unset($p->declared); $p->declared = 3;
unset($p->dyn); $p->dyn = 4;
#[\AllowDynamicProperties] class A {} class B extends A {}
$b = new B; $b->x = 1;
$s = new stdClass; $s->x = 1;
class S extends stdClass {} $ss = new S; $ss->x = 1;
$o = (object) ['a' => 1]; $o->b = 2;
$p->arr[] = 1;
$p->{'num'} = 5;
$name = "v"; $p->$name = 6;
$e = new Exception("x"); $e->extra = 1;
$ao = new ArrayObject([]); $ao->prop = 1;
readonly class R { public function __construct(public int $a = 1) {} }
$r = new R;
try { $r->b = 2; } catch (Error $e) { echo get_class($e), ": ", $e->getMessage(), "\n"; }
try { $r->c[] = 2; } catch (Error $e) { echo get_class($e), ": ", $e->getMessage(), "\n"; }
class M { public function __set($n, $v) { echo "__set $n\n"; } } $m = new M; $m->q = 1;
var_dump(array_keys(get_object_vars($p)));
restore_error_handler();

$objects = [
    'CurlHandle' => curl_init(), 'CurlMultiHandle' => curl_multi_init(), 'GdImage' => imagecreatetruecolor(1, 1),
    'IntlListFormatter' => new IntlListFormatter('en'), 'XMLParser' => xml_parser_create(), 'Directory' => dir(sys_get_temp_dir()),
    'WeakMap' => new WeakMap(), 'WeakReference' => WeakReference::create($p), 'Closure' => fn() => 1,
    'Generator' => (function () { yield 1; })(), 'Fiber' => new Fiber(fn() => 1), 'Random\Randomizer' => new Random\Randomizer(),
    'GMP' => gmp_init(1), 'DateTime' => new DateTime('@0'), 'SplStack' => new SplStack(),
];
foreach ($objects as $class => $object) {
    try {
        @$object->zz = 1;
        echo "$class allows it\n";
    } catch (Error $e) {
        echo $e->getMessage(), "\n";
    }
}

// a property write on anything but an object is an Error
foreach ([5, "s", 1.5, true, false, [1], null] as $v) {
    try {
        $v->a = 1;
    } catch (Error $e) {
        echo $e->getMessage(), "\n";
    }
}
