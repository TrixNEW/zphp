<?php
function spreadCallback($value) { return 'called:' . $value; }
$callback = Closure::fromCallable('spreadCallback');
echo $callback(...['spread']), "\n";
class SpreadReceiver {
    public function call($value) { return $value * 2; }
    public function __invoke(...$values) { return count($values); }
}
$receiver = new SpreadReceiver();
$method = Closure::fromCallable([$receiver, 'call']);
echo $method(...[21]), "\n";
$many = array_fill(0, 300, 1);
echo $receiver(...$many), "\n";
$invoke = Closure::fromCallable($receiver);
echo $invoke(...$many), "\n";
