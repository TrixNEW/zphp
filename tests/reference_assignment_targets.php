<?php

$source = 'start';
$array = [];
$array['key'] =& $source;
$array[] =& $source;

$object = new stdClass();
$object->value =& $source;
$dynamic = 'dynamic';
$object->$dynamic =& $source;

$source = 'source-write';
echo $array['key'], ' ', $array[0], ' ', $object->value, ' ', $object->dynamic, "\n";

$array['key'] = 'array-write';
echo $source, ' ', $array[0], ' ', $object->value, ' ', $object->dynamic, "\n";

$object->dynamic = 'object-write';
echo $source, ' ', $array['key'], ' ', $array[0], ' ', $object->value, "\n";
