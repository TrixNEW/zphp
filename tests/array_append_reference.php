<?php
// Appending by reference must create the next array element and bind the variable to it.

$values = [];
$ref = &$values[];
$ref = 'first';
$next = &$values[];
$next = 'second';
echo implode(',', $values), "\n";

$groups = [[[1]]];
$output = [];
foreach ($groups as &$items) {
    foreach ($items as &$item) {
        $slot = &$output[];
        $slot = $item;
    }
}
echo count($output), ':', $output[0][0], "\n";
