<?php
// a variable bound through a reference-returning function mirrors the shared
// storage without breaking copy-on-write for plain copies of it

function &serverRef() { return $_SERVER; }
$server =& serverRef();
$_SERVER['nested'] = ['value' => 'original'];
$snapshot = $_SERVER;
$_SERVER['nested']['value'] = 'top';
echo $_SERVER['nested']['value'], ':', $snapshot['nested']['value'], "\n";

function writeNested() { $_SERVER['nested']['value'] = 'function'; }
$snapshot = $_SERVER;
writeNested();
echo $_SERVER['nested']['value'], ':', $snapshot['nested']['value'], ':', $server['nested']['value'], "\n";

$server['through'] = 'alias';
$_SERVER['back'] = 'global';
echo $_SERVER['through'], ':', $server['back'], "\n";

$_SESSION = [];
function &sessionRef() { return $_SESSION; }
$session =& sessionRef();
$_SESSION['a'] = ['b' => 1];
$copy = $_SESSION;
$_SESSION['a']['b'] = 2;
echo $_SESSION['a']['b'], ':', $copy['a']['b'], ':', $session['a']['b'], "\n";

$g = ['k' => 1];
function &globalRef() { global $g; return $g; }
function viaLocal() {
    $y =& globalRef();
    $c = $y;
    $y['k'] = 2;
    global $g;
    echo $g['k'], ':', $c['k'], "\n";
    $y = ['k' => 3];
    echo $g['k'], ':', $c['k'], "\n";
}
viaLocal();

class Box { public $items = ['x' => 1]; public function &items() { return $this->items; } }
$box = new Box();
$items =& $box->items();
$before = $box->items;
$items['x'] = 2;
echo $box->items['x'], ':', $before['x'], "\n";
