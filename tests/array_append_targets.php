<?php
// `[]` as a write target anywhere a variable can be written
$a[] = 1;
$a[]["x"] = 2;
$a[][] = 3;
$a[]["k"][] = 4;
echo json_encode($a), "\n";

function inFunction() {
    $a = [];
    $a[]["x"]["y"][] = 1;
    $b = $a;
    $b[][0] = 2;
    return json_encode([$a, $b]);
}
echo inFunction(), "\n";

class Holder { public $list = []; }
$h = new Holder;
$h->list[]["k"] = 1;
$h->list[]["k"] = 2;
echo json_encode($h->list), "\n";

// compound assignment appends the operator applied to null
$c = [5];
$c[] .= "x";
$c[] += 1;
$c[] -= 2;
$c[] *= 3;
echo json_encode($c), "\n";

// foreach can write each key and value into any target
$vals = [];
$keys = [];
foreach (["p" => 1, "q" => 2] as $keys[] => $vals[]) {}
$o = new stdClass;
foreach ([1, 2, 3] as $o->last) {}
$m = [];
foreach ([[1, 2], [3, 4]] as [$m[], $m[]]) {}
echo json_encode([$keys, $vals, $o->last, $m]), "\n";

list(, $x[]) = [1, 2];
[$y["a"], [$y["b"]]] = [1, [2]];
echo json_encode([$x, $y]), "\n";

// errors raised when appending through something that is not an array
$s = "ab";
try { $s[]["x"] = 1; } catch (Error $e) { echo get_class($e), ": ", $e->getMessage(), "\n"; }
$i = 5;
try { $i[]["x"] = 1; } catch (Error $e) { echo get_class($e), ": ", $e->getMessage(), "\n"; }
