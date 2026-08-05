<?php

function set_value(&$value): void
{
    $value = 'updated';
}

function invoke_with_spread(callable $callback, array $arguments): void
{
    $callback(...$arguments);
}

$direct = null;
$direct_args = [&$direct];
set_value(...$direct_args);
var_dump($direct);

$indirect = null;
invoke_with_spread('set_value', [&$indirect]);
var_dump($indirect);

$matches = null;
invoke_with_spread('preg_match', ['~^([a-z]+)://(.+)$~', 'phar:///path', &$matches]);
var_dump($matches);
