<?php
$g = gmp_init(42); $big = gmp_init('123456789012345678901234567890'); $z = gmp_init(0);
foreach ([
  fn() => (int) $g, fn() => (float) $g, fn() => (string) $g, fn() => (bool) $z, fn() => (bool) $g,
  fn() => intval($g), fn() => floatval($g), fn() => (int) $big, fn() => (float) $big, fn() => is_numeric($g),
  fn() => array_sum([gmp_init(2), 3]), fn() => array_product([gmp_init(2), 3]), fn() => array_sum([$big]),
  fn() => array_sum([new stdClass, 1]), fn() => json_encode($g), fn() => "v=$g", fn() => $g . '!',
  fn() => settype($g, 'integer') ? $g : null,
] as $i => $f) {
  try { var_export($f()); echo "\n"; } catch (Throwable $e) { echo $i, ' ', get_class($e), ': ', $e->getMessage(), "\n"; }
}
