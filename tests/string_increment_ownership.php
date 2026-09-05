<?php
// string increment allocates a new string and releases the old one
function own($n) { return str_repeat('k', $n); }
$s = own(4); $s++; echo $s, "\n";
$a = ['kk' => own(4)]; $a['kk']++; $a['kk']++; echo $a['kk'], "\n";
function f() { $z = 'Az'; $z++; $z++; return $z; } echo f(), "\n";
for ($i = 0; $i < 5000; $i++) { $t = own(64); $t++; }
echo strlen($t), "\n";
