<?php
// ++, -- and compound assignment on every writable target evaluate each
// operand once and leave the right value behind

function trace(string $label, $value)
{
    echo "[$label]";
    return $value;
}

class Counter
{
    public static $hits = 1;
    public static $named = 10;
    public $n = 5;
    public $list = ['a' => 1];
}

$q = 1;
$name = 'q';
echo ++$$name, ' ', $$name++, ' ', $q, ' ', --$$name, ' ', $$name--, ' ', $q, "\n";
$$name += 10;
$$name .= 'x';
var_dump($q);

$o = new Counter();
$prop = 'n';
echo $o->$prop++, ' ', ++$o->$prop, ' ', $o->{'n'}--, ' ', --$o->{'n'}, ' ', $o->n, "\n";
$o->$prop += 3;
$o->{trace('name', 'n')} *= 2;
echo $o->n, "\n";

echo trace('obj', $o)->n++, ' ', ++trace('obj', $o)->n, ' ', $o->n, "\n";
echo trace('obj', $o)->{trace('p', 'n')}--, ' ', $o->n, "\n";

echo Counter::$hits++, ' ', ++Counter::$hits, ' ', Counter::$hits, "\n";
$sp = 'named';
echo Counter::$$sp++, ' ', ++Counter::$$sp, ' ', Counter::${'na' . 'med'}--, ' ', Counter::$named, "\n";
Counter::$$sp += 5;
$cls = 'Counter';
echo $cls::$hits++, ' ', ++$cls::$hits, ' ', $cls::$$sp--, ' ', Counter::$named, ' ', Counter::$hits, "\n";
$cls::$$sp -= 1;
echo Counter::$named, "\n";

$a = ['k' => 1, 'z' => 'Az'];
echo ++$a[trace('k', 'k')], ' ', $a[trace('k', 'k')]++, ' ', $a['k'], "\n";
$a[trace('k', 'k')] += 5;
$a[trace('z', 'z')]++;
$o->list[trace('a', 'a')] .= '!';
echo json_encode($a), ' ', json_encode($o->list), "\n";

$s = 'Az';
$s++;
$n = null;
$n--;
$m = null;
$m++;
var_dump($s, $n, $m);

$shared = [1, 2];
$copy = $shared;
$copy[0] += 10;
++$copy[1];
echo json_encode($shared), json_encode($copy), "\n";

function inc_local()
{
    $v = 'w';
    $$v = 1;
    $name = 'w';
    for ($i = 0; $i < 5; $i++) {
        $$name++;
        ++$$name;
    }
    return $w;
}
echo inc_local(), "\n";

set_error_handler(function (int $no, string $message) {
    echo "warning: $message\n";
    return true;
}, E_WARNING);
$null = null;
$null--;
--$null;
$flag = true;
$flag++;
$flag--;
$list = [null];
$list[0]--;
$empty = '';
$empty--;
var_dump($null, $flag, $list[0], $empty);
restore_error_handler();

$handle = fopen('php://memory', 'r');
foreach ([[1], new stdClass(), $handle] as $operand) {
    foreach (['inc', 'dec'] as $step) {
        try {
            $step === 'inc' ? $operand++ : $operand--;
        } catch (TypeError $e) {
            echo get_class($e), ': ', $e->getMessage(), "\n";
        }
    }
}
