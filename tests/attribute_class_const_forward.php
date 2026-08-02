<?php
#[Attribute(Attribute::TARGET_PROPERTY)]
class Link { public function __construct(public ?string $target = null) {} }
class Owner { #[Link(target: Target::class)] public string $value; }
class Target {}
$a=(new ReflectionClass(Owner::class))->getProperty('value')->getAttributes()[0];
var_dump($a->getArguments(), $a->newInstance()->target);
