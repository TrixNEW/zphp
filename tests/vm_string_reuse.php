<?php
// strings the vm makes on its own paths (string operators, character
// writes, the messages of caught errors, defaults, hooks) are reclaimed, so a
// loop that takes those paths runs in flat memory

const PREFIX = 'p';
class Hooked { public string $v { get => 'h' . $this->v; set => $value; } public function __construct() { $this->v = 'x'; } }
class Guarded { private $secret = 1; public readonly int $ro; public function __construct() { $this->ro = 1; } }
class TypedHolder { public int $n = 0; }
function two_params($a, $b) {}
function one_param($a) {}
function returns_int(): int { return 'zz'; }
function takes_int(int $i) {}
function with_default($x = PREFIX . 'suffix') { return $x; }
function uses_global() { global $shared; return $shared; }

$bad_include = sys_get_temp_dir() . '/zphp_string_reuse_bad_' . getmypid() . '.php';
file_put_contents($bad_include, "<?php if (");
$chars = new stdClass;
$chars->s = 'abcdef';
$nested = ['k' => 'abcdef'];
$cases = [
    'string bitwise not' => fn() => ~'abc',
    'string bitwise and' => fn() => 'abc' & 'xyz',
    'string bitwise xor' => fn() => 'abc' ^ 'xyz',
    'property char write' => function () use ($chars) { $chars->s[2] = 'z'; },
    'nested char write' => function () use (&$nested) { $nested['k'][2] = 'z'; },
    'undefined method' => function () use ($chars) { try { $chars->nope(); } catch (Error $e) {} },
    'undefined static method' => function () { try { TypedHolder::nope(); } catch (Error $e) {} },
    'too few arguments' => function () { try { two_params(1); } catch (ArgumentCountError $e) {} },
    'unknown named argument' => function () { try { one_param(b: 1); } catch (Error $e) {} },
    'private property' => function () { try { (new Guarded)->secret; } catch (Error $e) {} },
    'readonly property' => function () { $g = new Guarded; try { $g->ro = 2; } catch (Error $e) {} },
    'return type' => function () { try { returns_int(); } catch (TypeError $e) {} },
    'argument type' => function () { try { takes_int('zz'); } catch (TypeError $e) {} },
    'typed property' => function () { $t = new TypedHolder; try { $t->n = 'zz'; } catch (TypeError $e) {} },
    'offset type' => function () { $a = []; try { $a[[]] = 1; } catch (TypeError $e) {} },
    'eval parse error' => function () { try { eval('if ('); } catch (ParseError $e) {} },
    'include parse error' => function () use ($bad_include) { try { include $bad_include; } catch (ParseError $e) {} },
    'constant expression default' => fn() => with_default(),
    'property hook' => fn() => (new Hooked)->v,
    'global binding' => fn() => uses_global(),
];
foreach ($cases as $name => $make) {
    for ($i = 0; $i < 100; $i++) $make();
    $before = memory_get_usage();
    for ($i = 0; $i < 1000; $i++) $make();
    $grown = memory_get_usage() - $before;
    echo str_pad($name, 30), $grown < 16 * 1024 ? "flat" : "grew $grown bytes", "\n";
}
@unlink($bad_include);
