<?php
// SplHeap and its subclasses keep php's binary heap: compare() runs during
// insert and extract in php's order, ties come out in php's order, user
// compare() overrides apply to SplMinHeap and SplMaxHeap too, and an
// exception from compare() corrupts the heap until recoverFromCorruption()

class Item { public function __construct(public int $p, public string $tag) {} }

class ByPriority extends SplHeap {
    public array $log = [];
    protected function compare($a, $b): int { $this->log[] = "$a->tag$b->tag"; return $a->p <=> $b->p; }
}
$h = new ByPriority();
foreach ([[3, 'a'], [1, 'b'], [3, 'c'], [2, 'd'], [3, 'e'], [1, 'f'], [2, 'g']] as [$p, $t]) $h->insert(new Item($p, $t));
echo implode(',', $h->log), "\n";
$h->log = [];
$out = [];
while (!$h->isEmpty()) $out[] = $h->extract()->tag;
echo implode('', $out), ' ', implode(',', $h->log), "\n";

class ReverseMin extends SplMinHeap { protected function compare($a, $b): int { return parent::compare($b, $a); } }
$r = new ReverseMin();
foreach ([5, 1, 4, 2, 3] as $v) $r->insert($v);
echo implode(' ', iterator_to_array($r, false)), "\n";

$min = new SplMinHeap();
$max = new SplMaxHeap();
foreach ([[2, 'x'], [1, 'y'], [2, 'z'], [1, 'w'], [3, 'v']] as $pair) { $min->insert($pair); $max->insert($pair); }
foreach ($min as $k => $v) echo "$k:$v[0]$v[1] ";
echo "\n";
echo count($max), ' ', $max->top()[1], ' ', $max->key(), "\n";
foreach ($max as $k => $v) echo "$k:$v[0]$v[1] ";
echo count($max), "\n";

class Throwing extends SplHeap {
    public int $calls = 0;
    protected function compare($a, $b): int { if (++$this->calls === 3) throw new RuntimeException("boom"); return $a <=> $b; }
}
$t = new Throwing();
try { foreach ([1, 2, 3, 4] as $v) $t->insert($v); } catch (RuntimeException $e) { echo "caught: ", $e->getMessage(), "\n"; }
var_dump($t->isCorrupted(), count($t));
foreach (['insert', 'extract', 'top'] as $m) {
    try { $m === 'insert' ? $t->insert(9) : $t->$m(); } catch (RuntimeException $e) { echo "$m: ", $e->getMessage(), "\n"; }
}
var_dump($t->recoverFromCorruption(), $t->isCorrupted());
$left = [];
while ($t->valid()) { $left[] = $t->current(); $t->next(); }
echo implode(',', $left), "\n";

try { (new SplMinHeap())->extract(); } catch (RuntimeException $e) { echo $e->getMessage(), "\n"; }
try { (new SplMaxHeap())->top(); } catch (RuntimeException $e) { echo $e->getMessage(), "\n"; }
var_dump((new SplMinHeap())->current());
