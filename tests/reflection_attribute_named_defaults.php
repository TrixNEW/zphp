<?php

#[Attribute(Attribute::TARGET_PROPERTY)]
class NamedDefaults
{
    public function __construct(
        public readonly ?string $first = null,
        public readonly ?string $second = null,
        public readonly array $items = [],
        public readonly string $mode = 'default',
        public readonly bool $enabled = false,
    ) {}
}

class NamedTarget
{
    #[NamedDefaults(second: 'two', enabled: true)]
    public string $value;
}

$instance = (new ReflectionClass(NamedTarget::class))->getProperty('value')->getAttributes()[0]->newInstance();
var_dump($instance->first, $instance->second, $instance->items, $instance->mode, $instance->enabled);
