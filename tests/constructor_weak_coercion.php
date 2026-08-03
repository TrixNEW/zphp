<?php

class ConstructorCoercion
{
    public function __construct(
        public ?string $string,
        public int $integer,
        public float $float,
        public bool $boolean,
    ) {}
}

$value = new ConstructorCoercion(0, '12', '1.5', 1);
var_dump($value->string, $value->integer, $value->float, $value->boolean);
