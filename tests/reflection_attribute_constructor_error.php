<?php

#[Attribute(Attribute::TARGET_PROPERTY)]
class FailingAttribute
{
    public function __construct(public string $value)
    {
        throw new RuntimeException("attribute failed: $value");
    }
}

class Target
{
    #[FailingAttribute('test')]
    public string $property;
}

try {
    (new ReflectionClass(Target::class))->getProperty('property')->getAttributes()[0]->newInstance();
} catch (Throwable $e) {
    echo get_class($e), ': ', $e->getMessage(), "\n";
}
