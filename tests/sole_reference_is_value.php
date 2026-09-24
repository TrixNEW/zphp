<?php
class Ref { function __construct(public $v) {} }
class St { function __construct(private $e) {} function getEntity() { return $this->e; } }
class Res {
    private function normalize(St $s) {
        $entity = $s->getEntity();
        if (is_array($entity)) { $item =& $entity[0]; } else { $item =& $entity; }
        if ($item instanceof Ref) { $item = new Ref($item->v . '!'); }
        return $entity;
    }
    public function type(St $s) {
        $entity = $this->normalize($s);
        if (is_array($entity)) {
            if ($entity[0] instanceof Ref) {
                $entity[0] = $this->type(new St($entity[0]));
            }
            return $entity;
        }
        return 'T:' . $entity->v;
    }
}
$r = new Res;
var_dump($r->type(new St([new Ref('svc'), 'create'])));
function f() { $a = ['x', 'y']; $i =& $a[0]; $i = 'z'; return $a; }
$b = f(); $b[0] = 'w'; var_dump($b);
function g() { $a = ['x', 'y']; $i =& $a[0]; return $a; }
$c = g(); $c[0] = 'w'; var_dump($c);
// same scope, ref alive: copy shares the ref slot
$a = ['x', 'y']; $i =& $a[0]; $c = $a; $c[0] = 'w'; echo json_encode([$a, $c, $i]), "\n";
// same scope, ref dropped
$a = ['x', 'y']; $i =& $a[0]; unset($i); $c = $a; $c[0] = 'w'; echo json_encode([$a, $c]), "\n";
// write to the original after unset
$a = ['x', 'y']; $i =& $a[0]; unset($i); $a[0] = 'w'; echo json_encode($a), "\n";
// write to the original with ref alive
$a = ['x', 'y']; $i =& $a[0]; $a[0] = 'w'; echo json_encode([$a, $i]), "\n";
$c = g(); $c[0] = 'w'; echo json_encode($c), "\n";
$c = g(); $c[1] = 'w'; echo json_encode($c), "\n";
function h() { $a = ['x', 'y']; $i =& $a[0]; unset($i); return $a; }
$c = h(); $c[0] = 'w'; echo json_encode($c), "\n";
$c = g(); $d = $c; $c[0] = 'w'; echo json_encode([$c, $d]), "\n";
$c = g(); $d = $c; $d[0] = 'w'; echo json_encode([$c, $d]), "\n";
$a = ['x', 'y']; $i =& $a[0]; $d = $a; unset($i); $d[0] = 'w'; echo json_encode([$a, $d]), "\n";
$a = ['x', 'y']; $i =& $a[0]; $d = $a; unset($i); $a[0] = 'v'; echo json_encode([$a, $d]), "\n";
$c = g(); $j =& $c[0]; $j = 'q'; echo json_encode($c), "\n";
$c = g(); foreach ($c as &$v) { $v .= '!'; } unset($v); echo json_encode($c), "\n";
$c = g(); $j =& $c[0]; $j = "q"; echo json_encode($c), "\n";
function k() { $c = g(); $j =& $c[0]; $j = "q"; echo json_encode($c), "\n"; }
k();
