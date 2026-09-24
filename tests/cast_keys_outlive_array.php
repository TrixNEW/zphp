<?php
#[\AllowDynamicProperties]
class Point { public $x = 1; protected $y = 2; private $z = 3; }

function row(): array {
    $row = [];
    foreach (['id', 'name', 'email'] as $column) {
        $row[strtoupper($column[0]) . substr($column, 1)] = str_repeat($column, 2);
    }
    $row[40 + 2] = 'answer';
    return $row;
}

function churn(): void {
    $junk = [];
    for ($i = 0; $i < 200; $i++) {
        $junk[] = str_repeat('x', 12) . $i;
    }
}

$cast = (fn($value) => (object) $value)(row());
churn();
print_r($cast);

$set = (static function ($value) {
    settype($value, 'object');
    return $value;
})(row());
churn();
print_r($set);

$copy = new stdClass;
foreach (row() as $k => $v) {
    $copy->{$k} = $v;
}
churn();
print_r(get_object_vars($copy));

$point = new Point;
$point->extra = 'dynamic';
$arr = $point;
settype($arr, 'array');
var_dump(array_map('strlen', array_keys($arr)));
var_dump(array_keys((array) $point) === array_keys($arr));

$scalar = 5;
settype($scalar, 'object');
print_r($scalar);
$null = null;
settype($null, 'array');
var_dump($null);
