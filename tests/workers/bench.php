<?php
// cpu-bound scaling: the same work split over 1, 2, 4, and 8 workers
$tasks = 32;
$per_task = 300000;
foreach ([1, 2, 4, 8] as $workers) {
    $pool = new Zphp\Pool(workers: $workers, bootstrap: __DIR__ . "/worker.php");
    $start = hrtime(true);
    $futures = [];
    for ($i = 0; $i < $tasks; $i++) $futures[] = $pool->submit('spin', [$per_task]);
    foreach ($futures as $f) $f->await();
    $ms = (hrtime(true) - $start) / 1e6;
    printf("%d workers: %.0f ms\n", $workers, $ms);
    $pool->shutdown();
}

// a 16 MB round trip: a string is copied both ways, a buffer's bytes move
$pool = new Zphp\Pool(1);
$s = str_repeat("x", 16 << 20);
$b = new Zphp\Buffer(16 << 20);
$start = hrtime(true);
for ($i = 0; $i < 10; $i++) $s = $pool->submit(fn(string $s) => $s, [$s])->await();
printf("string transfer: %.0f us\n", (hrtime(true) - $start) / 10e3);
$start = hrtime(true);
for ($i = 0; $i < 10; $i++) $b = $pool->submit(fn(Zphp\Buffer $b) => $b, [$b])->await();
printf("buffer transfer: %.0f us\n", (hrtime(true) - $start) / 10e3);
$pool->shutdown();
