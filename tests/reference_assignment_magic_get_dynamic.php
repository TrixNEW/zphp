<?php

class ReferenceAssignmentMagicGetDynamic
{
    private array $values = ['name' => 'before'];

    public function &__get(string $name): mixed
    {
        return $this->values[$name];
    }

    public function read(string $name): mixed
    {
        return $this->values[$name];
    }
}

$object = new ReferenceAssignmentMagicGetDynamic();
$property = 'name';
$alias =& $object->$property;
$alias = 'through-alias';
echo $object->read('name'), "\n";
$alias = 'again';
echo $object->read('name'), "\n";
