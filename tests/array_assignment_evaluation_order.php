<?php

$state = 0;
$array = [];

function dimension(): int
{
    global $state;
    $state = 1;
    return 0;
}

$result = $array[dimension()] = $state;
var_dump($array, $result, $state);

$source = ['value' => 1];
$result = $array[1] = $source;
$source['value'] = 2;
var_dump($array[1], $result, $source);
