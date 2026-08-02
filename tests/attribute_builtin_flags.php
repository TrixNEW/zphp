<?php

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class RepeatableProperty {}

$attribute = (new ReflectionClass(RepeatableProperty::class))->getAttributes()[0];
$instance = $attribute->newInstance();
var_dump($instance->flags);
var_dump(($instance->flags & Attribute::IS_REPEATABLE) > 0);

$default = (new ReflectionClass(Attribute::class))->newInstance();
var_dump($default->flags);
