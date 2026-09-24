<?php
$g = fn($n) => gmp_init($n);
$show = fn($v) => is_array($v) ? '[' . implode(',', array_map(fn($x) => is_object($x) ? gmp_strval($x) : var_export($x, true), $v)) . ']' : (is_object($v) ? 'GMP:' . gmp_strval($v) : var_export($v, true));
$cases = [
  'max' => fn() => max($g(3), $g(9), $g(5)),
  'min' => fn() => min([$g(3), $g(-9)]),
  'array_sum' => fn() => array_sum([$g(2), 3]),
  'array_product' => fn() => array_product([$g(2), 3]),
  'sort' => function () use ($g) { $a = [$g(3), $g(1), $g(2)]; sort($a); return $a; },
  'rsort' => function () use ($g) { $a = [$g(3), $g(10), $g(2)]; rsort($a); return $a; },
  'asort' => function () use ($g) { $a = ['x' => $g(3), 'y' => $g(1)]; asort($a); return array_keys($a); },
  'in_array' => fn() => in_array(5, [$g(4), $g(5)]),
  'array_search' => fn() => array_search('5', [$g(4), $g(5)]),
  'array_unique' => fn() => count(array_unique([$g(1), $g(1), $g(2)], SORT_REGULAR)),
  'usort default <=>' => function () use ($g) { $a = [$g(3), $g(1)]; usort($a, fn($x, $y) => $x <=> $y); return $a; },
];
foreach ($cases as $k => $f) { try { echo $k, ': ', $show($f()), "\n"; } catch (Throwable $e) { echo $k, ': ', get_class($e), ' ', $e->getMessage(), "\n"; } }
