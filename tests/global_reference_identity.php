<?php

$value = 'initial';
$alias =& $value;

function globalReferenceWrite(): void
{
    global $value;
    $value = 'function';
}

function globalReferenceRebind(): void
{
    global $value;
    $other = 'other';
    $value =& $other;
    $value = 'rebound';
}

globalReferenceWrite();
echo "$value $alias\n";
$alias = 'alias';
echo "$value $alias\n";
globalReferenceRebind();
echo "$value $alias\n";
$value = 'top';
echo "$value $alias\n";
