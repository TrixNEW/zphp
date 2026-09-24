<?php
foreach ([fn() => ~5, fn() => ~5.7, fn() => bin2hex(~"ab"), fn() => ~true, fn() => ~null, fn() => ~[], fn() => ~new stdClass, fn() => new stdClass << 1, fn() => 1 >> new stdClass, fn() => [] << 1, fn() => new BcMath\Number(5) & 1, fn() => "8" << 1, fn() => null << 1] as $i => $f) {
    try { var_dump($f()); } catch (Throwable $e) { echo $i, ' ', get_class($e), ': ', $e->getMessage(), "\n"; }
}
