<?php
$fiber = new Fiber(function () {
    $value = 4;
    $ref = &$value;
    Fiber::suspend(function () use (&$ref) { return ++$ref; });
    return $value;
});
$callback = $fiber->start();
var_dump($callback());
$fiber->resume();
var_dump($fiber->getReturn());
unset($fiber);
var_dump($callback());
