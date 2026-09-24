<?php
$a = gmp_init(-7); $b = gmp_init(3);
$cases = [
  'a+b' => fn() => $a + $b, 'a+1' => fn() => $a + 1, '1+a' => fn() => 1 + $a, 'a+"5"' => fn() => $a + "5",
  'a-b' => fn() => $a - $b, '10-a' => fn() => 10 - $a, 'a*b' => fn() => $a * $b, 'a/b' => fn() => $a / $b, '7/-2' => fn() => gmp_init(7) / -2,
  'a%b' => fn() => $a % $b, '7%-3' => fn() => gmp_init(7) % -3, 'a**2' => fn() => $a ** 2, '2**b' => fn() => 2 ** $b, 'a**b' => fn() => $a ** $b,
  '-a' => fn() => -$a, '~a' => fn() => ~$a, 'a&b' => fn() => $a & $b, 'a|b' => fn() => $a | $b, 'a^b' => fn() => $a ^ $b,
  'a<<3' => fn() => $a << 3, 'a>>1' => fn() => $a >> 1, '1<<b' => fn() => 1 << $b, 'a<<-1' => fn() => $a << -1,
  'a<b' => fn() => $a < $b, 'a==-7' => fn() => $a == -7, 'a<=>b' => fn() => $a <=> $b, 'b=="3"' => fn() => $b == "3", 'a==b' => fn() => $a == $b,
  'a+1.5' => fn() => $a + 1.5, 'a+"x"' => fn() => $a + "x", 'a+[]' => fn() => $a + [], 'a/0' => fn() => $a / 0, 'a%0' => fn() => $a % 0,
  'a+true' => fn() => $a + true, 'a+null' => fn() => $a + null, 'a**-1' => fn() => $a ** -1, 'a.=' => function() use ($a) { $c = $a; $c += 5; $c *= 2; $c <<= 1; return $c; },
  'a**b big' => fn() => gmp_init(2) ** 100,
];
foreach ($cases as $k => $f) {
  try { $r = $f(); echo $k, ' => ', is_object($r) ? 'GMP(' . gmp_strval($r) . ')' : var_export($r, true), "\n"; }
  catch (Throwable $e) { echo $k, ' => ', get_class($e), ': ', $e->getMessage(), "\n"; }
}
