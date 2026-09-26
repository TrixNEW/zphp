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
