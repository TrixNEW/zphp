<?php
// strings the runtime makes for a conversion or a native's result are
// reclaimed like any other value, so a loop that makes and drops them runs
// in flat memory

class Label {
    public string $text = '';
    public function __construct(private string $name) {}
    public function __toString(): string { return 'label:' . $this->name . mt_rand(0, 9); }
}
enum IntBacked: int { case One = 1; }
function takes_string(string $s): int { return strlen($s); }
function returns_string(): string { return new Label('r'); }
function returns_int_as_string(): string { return mt_rand(); }
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

$csv = sys_get_temp_dir() . '/zphp_string_reuse_' . getmypid() . '.csv';
file_put_contents($csv, "a,\"b c\",d\n1,2,3\n");
$label = new Label('x');
$holder = new Label('h');
$bad_include = sys_get_temp_dir() . '/zphp_string_reuse_bad_' . getmypid() . '.php';
file_put_contents($bad_include, "<?php if (");
$chars = new stdClass;
$chars->s = 'abcdef';
$nested = ['k' => 'abcdef'];
$cases = [
    'cast object' => fn() => (string) $label,
    'interpolate object' => fn() => "[$label]",
    'concat object' => fn() => 'a' . $label,
    'echo object' => function () use ($label) { ob_start(); echo $label; ob_end_clean(); },
    'object to string parameter' => fn() => takes_string($label),
    'int to string parameter' => fn() => takes_string(mt_rand()),
    'object as string return' => fn() => returns_string(),
    'int as string return' => fn() => returns_int_as_string(),
    'object into string property' => function () use ($holder, $label) { $holder->text = $label; },
    'int into string property' => function () use ($holder) { $holder->text = mt_rand(); },
    'dynamic property name' => function () { $o = new stdClass; $n = mt_rand(); $o->$n = 1; return $o; },
    'exception as string' => fn() => (string) new RuntimeException('m'),
    'json_decode failure' => fn() => json_decode('{"a": [1, {"b": }]}'),
    'json_decode trailing' => fn() => json_decode('[1, {"a": [2]}] x'),
    'json_decode exception' => function () { try { json_decode('{', flags: JSON_THROW_ON_ERROR); } catch (JsonException $e) {} },
    'fgetcsv' => function () use ($csv) { $h = fopen($csv, 'r'); while (fgetcsv($h, escape: '\\') !== false); fclose($h); },
    'file_put_contents array' => fn() => file_put_contents($csv . '.out', ['a', 1, 2.5]),
    'data uri stream' => function () { $h = fopen('data://text/plain;base64,SGVsbG8=', 'r'); fread($h, 5); fclose($h); },
    'getopt' => fn() => getopt('ab:', ['long:']),
    'getenv all' => fn() => getenv(),
    'strtok restart' => function () { strtok('a b c', ' '); strtok(' '); },
    'is_callable name' => function () { is_callable('strlen', false, $name); },
    'serialize internal object' => fn() => serialize(new ArrayObject([1])),
    'DateTime with offset' => fn() => new DateTime('2020-01-01 10:00:00+02:00'),
    'sprintf error' => function () { try { sprintf('%y', 1); } catch (ValueError $e) {} },
    'reflection parameters' => fn() => (new ReflectionMethod('ArrayObject', 'offsetGet'))->getParameters()[0]->getDeclaringFunction()->getName(),
    'enum tryFrom' => fn() => IntBacked::tryFrom(mt_rand(5, 9)),
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
    'stream wrapper cycle' => function () { stream_wrapper_register('reuse', 'TypedHolder'); stream_wrapper_unregister('reuse'); },
    'filter regexp int' => fn() => filter_var(123, FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/^1/']]),
];
if (extension_loaded('pdo_sqlite')) {
    $db = new PDO('sqlite::memory:');
    $db->sqliteCreateFunction('slen', fn($s) => strlen($s), 1);
    $db->sqliteCreateAggregate('scat', fn($c, $n, $s) => ($c ?? '') . $s, fn($c, $n) => $c, 1);
    $db->sqliteCreateCollation('rev', fn($a, $b) => strcmp($b, $a));
    $db->exec("create table t (s text)");
    $db->exec("insert into t values ('a'), ('b'), ('c')");
    $cases['sqlite function'] = fn() => $db->query("select slen('abc')")->fetchColumn();
    $cases['sqlite aggregate'] = fn() => $db->query("select scat(s) from t")->fetchColumn();
    $cases['sqlite collation'] = fn() => $db->query("select s from t order by s collate rev")->fetchAll();
}
if (extension_loaded('curl')) {
    $curl = curl_init();
    $cases['curl string options'] = function () use ($curl) {
        curl_setopt($curl, CURLOPT_URL, 'http://127.0.0.1/x');
        curl_setopt($curl, CURLOPT_POSTFIELDS, 'a=1&b=2');
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['X-A: 1']);
    };
}
if (extension_loaded('gmp')) {
    $cases['gmp from string'] = fn() => gmp_add('123456789012345678901234567890', 1);
}
foreach ($cases as $name => $make) {
    for ($i = 0; $i < 100; $i++) $make();
    $before = memory_get_usage();
    for ($i = 0; $i < 1000; $i++) $make();
    $grown = memory_get_usage() - $before;
    echo str_pad($name, 30), $grown < 16 * 1024 ? "flat" : "grew $grown bytes", "\n";
}
@unlink($csv);
@unlink($csv . '.out');
@unlink($bad_include);
