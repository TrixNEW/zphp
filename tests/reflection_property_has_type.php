<?php

class PropertyTypes
{
    private int $typed;
    private ?string $nullable;
    private $untyped;
    public function __construct(private stdClass $promoted) {}
}

$reflection = new ReflectionClass(PropertyTypes::class);
foreach (['typed', 'nullable', 'untyped', 'promoted'] as $name) {
    $property = $reflection->getProperty($name);
    echo $name, ':', $property->hasType() ? (string) $property->getType() : 'none', "\n";
}
