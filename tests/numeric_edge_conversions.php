<?php
// floats that cannot become ints are rejected, not truncated past the range
function takesInt(int $a) { return $a; }
foreach ([3.0, -9223372036854775808.0, 9233720368547758084.0, INF, -INF, NAN, "12", " 7", "9223372036854775807", "9223372036854775808", "1e30", 1e18] as $v) {
    try { echo var_export(takesInt($v), true), "\n"; } catch (TypeError $e) { echo "TypeError\n"; }
}

enum IntBacked: int { case One = 1; }
enum StrBacked: string { case Half = "1.5"; case One = "1"; case Three = "3"; }
foreach ([1.0, 1e30, NAN, INF] as $f) {
    try { var_dump(IntBacked::tryFrom($f)); } catch (TypeError $e) { echo "TypeError\n"; }
}
var_dump(StrBacked::tryFrom(3.0), StrBacked::tryFrom("1.5"));

// rounding and formatting at the extremes of the double range
foreach ([1e300, -1e300, 2.5, 3.5, 1e16 + 0.5] as $f) {
    var_dump(round($f, 0, PHP_ROUND_HALF_EVEN), round($f, 0, PHP_ROUND_HALF_ODD));
}
echo number_format(1e300, 0, '', ''), "\n";
echo number_format(-1.7976931348623157e308, 3), "\n";
echo number_format(1.5, 70), "\n";
echo number_format(0.1, 20), "\n";
echo number_format(PHP_INT_MAX), "\n";

// var_dump and var_export print whole numbers up to 17 digits in full
var_dump(1e15, 1e16, 1e17, 1.5e16, 1234567890123456.7, -1e16, 0.0001, 0.00001);
var_export([1e15, 1e16, 1e17, -0.0, 3.0]);
echo "\n", json_encode([1e15, 1e16, 1e17]), "\n";
echo 1e15, " ", 1e16, "\n";
