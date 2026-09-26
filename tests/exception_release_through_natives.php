<?php
// a caught throwable is freed when its last reference goes, whether it was
// thrown directly or through a native that called back into user code
class E extends Exception { function __destruct() { echo "E gone: ", $this->getMessage(), "\n"; } }
function thrower($v) { throw new E("direct"); }
try { thrower(1); } catch (E $e) {} $e = null; echo "1 done\n";
try { array_map('thrower', [1]); } catch (E $e) {} $e = null; echo "2 done\n";
try { array_map(fn($v) => throw new E("arrow"), [1]); } catch (E $e) {} $e = null; echo "3 done\n";
try { usort($a, fn($x, $y) => 0); } catch (Throwable $e) {} $e = null;
$list = [2, 1];
try { usort($list, function ($x, $y) { throw new E("usort"); }); } catch (E $e) {} $e = null; echo "4 done\n";
try { call_user_func('thrower', 1); } catch (E $e) {} $e = null; echo "5 done\n";
try { (new ArrayObject([1]))->uasort(function ($x, $y) { throw new E("method"); }); } catch (E $e) {} $e = null; echo "6 done\n";
