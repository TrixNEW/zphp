<?php

abstract class Enum
{
    public static function name(): string
    {
        return 'legacy';
    }
}

class ConcreteEnum extends Enum {}

echo ConcreteEnum::name(), "\n";
