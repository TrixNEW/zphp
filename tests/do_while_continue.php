<?php
$i = 0;
do {
    $i++;
    if ($i < 100) continue;
    echo "body end $i\n";
} while (false);
echo "after $i\n";
$n = 0;
do {
    $n++;
    if ($n % 2) continue;
    echo "even $n\n";
} while ($n < 5);
function f() { $k = 0; do { $k++; if (true) continue; } while ($k < 3); return $k; }
echo f(), "\n";
foreach ([1, 2] as $x) { do { continue 2; } while (true); }
echo "done\n";
