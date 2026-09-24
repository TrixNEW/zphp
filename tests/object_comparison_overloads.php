<?php
class Version { public function __construct(private string $v) {} public function __toString(): string { return $this->v; } }
foreach ([
  fn() => (string) -(new BcMath\Number('2.5')),
  fn() => new Version('b') < 'c',
  fn() => 'a' > new Version('b'),
  fn() => new Version('10') >= '9',
  fn() => gmp_init(1) == 'abc',
  fn() => gmp_init(5) < '10',
  fn() => max(gmp_init(3), gmp_init(9)),
  fn() => array_sum([gmp_init(2), 3]),
] as $i => $f) {
  try { $r = $f(); echo $i, ' ', is_object($r) ? get_class($r) . ':' . $r : var_export($r, true), "\n"; } catch (Throwable $e) { echo $i, ' ', get_class($e), ': ', $e->getMessage(), "\n"; }
}
class Broken { public function __toString(): string { throw new RuntimeException('no string'); } }
try { var_dump(new Broken < 'x'); } catch (RuntimeException $e) { echo 'caught ', $e->getMessage(), "\n"; }
try { var_dump(new Broken == 'x'); } catch (RuntimeException $e) { echo 'caught ', $e->getMessage(), "\n"; }
try { var_dump(gmp_init(2) <=> 'zz'); } catch (ValueError $e) { echo 'caught ', $e->getMessage(), "\n"; }
echo "after\n";
