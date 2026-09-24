<?php
class Invokable { public function __invoke() {} }
class Svc { public static function make() {} public function run() {} }

$cases = [
    'known class string' => 'Svc',
    'unknown namespaced string' => 'Nope\\Nope',
    'static method string' => 'a::b',
    'empty string' => '',
    'class and method' => ['X', 'y'],
    'object and method' => [new stdClass, 'x'],
    'single element' => ['X'],
    'non-string method' => ['X', 1],
    'ints' => [1, 2],
    'int' => 5,
    'closure' => fn() => 1,
    'invokable' => new Invokable,
    'plain object' => new stdClass,
];
foreach ($cases as $label => $value) {
    echo $label, ': ', var_export(is_callable($value, true), true), ' ', var_export(is_callable($value), true), "\n";
}
is_callable(['Svc', 'run'], true, $name);
echo $name, "\n";
is_callable('Nope\\Nope', true, $name);
echo $name, "\n";
