<?php

class Alpha
{
    public function value(): string
    {
        return 'alpha';
    }
}

class Factory
{
    public array $classes = ['item' => Alpha::class];
}

$factory = new Factory();
$object = new $factory->classes['item']();
echo $object->value(), "\n";
