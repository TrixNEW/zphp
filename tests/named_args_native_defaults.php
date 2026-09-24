<?php
$cases = [
    'skipped defaults' => fn() => json_decode('{"a":1}', flags: JSON_THROW_ON_ERROR),
    'skipped defaults throw' => fn() => json_decode('{"a"', flags: JSON_THROW_ON_ERROR),
    'object as array' => fn() => json_decode('{"a":1}', flags: JSON_OBJECT_AS_ARRAY),
    'string default' => fn() => str_pad('a', 3, pad_type: STR_PAD_LEFT),
    'bool default' => fn() => array_slice([1, 2, 3], 1, preserve_keys: true),
    'null default' => fn() => array_slice(['a' => 1, 'b' => 2, 'c' => 3], offset: 1, preserve_keys: false),
    'reordered' => fn() => str_pad(pad_string: '-', length: 4, string: 'x'),
    'by reference' => function () { preg_match('/(b)/', 'abc', flags: PREG_OFFSET_CAPTURE, matches: $m); return $m; },
    'required skipped' => fn() => json_decode(flags: 0),
    'unknown default' => fn() => mt_rand(max: 5),
    'variadic' => fn() => sprintf(format: '%s', values: 'x'),
    'unknown name' => fn() => strlen(string: 'abc', string2: 1),
    'overwrites' => fn() => strlen('abc', string: 'x'),
    'method' => fn() => (new DateTime('2026-01-02 03:04:05'))->format(format: 'Y-m-d'),
    'constructor' => fn() => (new ArrayObject(flags: ArrayObject::ARRAY_AS_PROPS, array: ['k' => 'v']))->k,
    'static' => fn() => DateTime::createFromFormat(format: 'Y-m-d', datetime: '2026-05-06')->format('md'),
    'enum default' => fn() => round(2.5, mode: PHP_ROUND_HALF_EVEN),
    'float default' => fn() => round(num: 1.23456, precision: 2),
];
foreach ($cases as $name => $case) {
    echo $name, ': ';
    try {
        echo json_encode($case()), "\n";
    } catch (Throwable $e) {
        echo get_class($e), ': ', $e->getMessage(), "\n";
    }
}
