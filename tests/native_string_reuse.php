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

$csv = sys_get_temp_dir() . '/zphp_string_reuse_' . getmypid() . '.csv';
file_put_contents($csv, "a,\"b c\",d\n1,2,3\n");
$label = new Label('x');
$holder = new Label('h');
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
];
if (extension_loaded('gmp')) {
    $cases['gmp from string'] = fn() => gmp_add('123456789012345678901234567890', 1);
}
foreach ($cases as $name => $make) {
    for ($i = 0; $i < 200; $i++) $make();
    $before = memory_get_usage();
    for ($i = 0; $i < 3000; $i++) $make();
    $grown = memory_get_usage() - $before;
    echo str_pad($name, 30), $grown < 16 * 1024 ? "flat" : "grew $grown bytes", "\n";
}
@unlink($csv);
@unlink($csv . '.out');
