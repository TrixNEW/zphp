<?php

function &referenceReturnDynamicProperty(object $object, string $property)
{
    return $object->$property;
}

$object = (object) ['value' => 'before'];
$alias =& referenceReturnDynamicProperty($object, 'value');
$alias = 'through-alias';
echo $object->value, "\n";
$object->value = 'through-property';
echo $alias, "\n";
