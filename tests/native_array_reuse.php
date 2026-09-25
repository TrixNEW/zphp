<?php
// arrays that natives return are recycled once released, even when nothing
// else in the loop allocates arrays
function churn(int $n): void
{
    for ($i = 0; $i < $n; $i++) {
        $algos = password_algos();
        $date = getdate(0);
        $parsed = date_parse('2020-01-02 03:04:05');
        $info = password_get_info('x');
        unset($algos, $date, $parsed, $info);
    }
}

churn(2000);
$before = memory_get_usage();
churn(50000);
$growth = memory_get_usage() - $before;
echo ($growth < 2 * 1024 * 1024) ? "bounded\n" : "unbounded: $growth\n";
