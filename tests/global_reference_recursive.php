<?php

$value = 0;
$alias =& $value;

function globalReferenceRecursive(int $depth): void
{
    global $value;
    if ($depth > 0) {
        globalReferenceRecursive($depth - 1);
    }
    $value++;
}

globalReferenceRecursive(3);
echo "$value $alias {$GLOBALS['value']}\n";
$GLOBALS['value'] = 10;
echo "$value $alias {$GLOBALS['value']}\n";
$alias = 20;
echo "$value $alias {$GLOBALS['value']}\n";
