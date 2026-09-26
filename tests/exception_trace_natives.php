<?php
// a trace names every native call on the stack where php's frame chain has
// it: a native that throws, a native that calls back into user code, and
// natives nested inside callbacks, all from the moment the throwable is made

function own_throw() { random_bytes(-1); }
try { own_throw(); } catch (ValueError $e) { echo $e->getTraceAsString(), "\n\n"; }

function inner_fail($x) { throw new RuntimeException("inner $x"); }
function callback($v) { inner_fail($v * 2); }
try { array_map('callback', [1]); } catch (RuntimeException $e) { echo $e->getTraceAsString(), "\n\n"; }

function divides() { return array_map(fn($v) => intdiv($v, 0), [4]); }
try { divides(); } catch (DivisionByZeroError $e) { echo $e->getTraceAsString(), "\n\n"; }

$saved = null;
try { throw new LogicException("early"); } catch (LogicException $e) { $saved = $e; }
try { array_map(function ($v) use ($saved) { throw $saved; }, [1]); } catch (LogicException $e) { echo $e->getTraceAsString(), "\n\n"; }

class Box implements IteratorAggregate {
    public function getIterator(): Iterator { throw new DomainException("no iterator"); }
}
try { iterator_to_array(new Box()); } catch (DomainException $e) { echo $e->getTraceAsString(), "\n\n"; }

try { usort($list, fn($a, $b) => throw new Exception("cmp")); } catch (Throwable $e) { echo get_class($e), "\n\n"; }
$list = [3, 1, 2];
try { usort($list, fn($a, $b) => throw new Exception("cmp")); } catch (Exception $e) { echo $e->getTraceAsString(), "\n\n"; }

echo (string) new Exception("", 0, new InvalidArgumentException("cause")), "\n";

function takes_text($s) { throw new Exception("text"); }
try { takes_text("App\\Models\\User\\Profile"); } catch (Exception $e) { echo $e->getTraceAsString(), "\n"; }
try { takes_text("a\\b\n\t\x01\xC3\xA9\x7F\"'z"); } catch (Exception $e) { echo $e->getTraceAsString(), "\n"; }

function with_default($a, $b = 2) { throw new Exception("default"); }
function spread($first, ...$rest) { throw new Exception("variadic"); }
function no_params() { throw new Exception("extra"); }
function reassigns($x) { $x = 99; throw new Exception("reassigned"); }
foreach ([fn() => with_default(1), fn() => spread(1, 2, 3), fn() => no_params(7, 8), fn() => reassigns(1), fn() => array_map('no_params', [5])] as $call) {
    try { $call(); } catch (Exception $e) { echo $e->getTrace()[0]['function'], ': ', json_encode($e->getTrace()[0]['args']), "\n"; }
}
