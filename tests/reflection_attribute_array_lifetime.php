<?php
#[Attribute]
class ArrayLifetimeAttribute {
    public function __construct(public array $values) {}
}
#[ArrayLifetimeAttribute(['nested' => ['original']])]
function attributedArrayLifetime() {}
for ($i = 0; $i < 3; $i++) {
    $attribute = (new ReflectionFunction('attributedArrayLifetime'))->getAttributes()[0];
    $args = $attribute->getArguments();
    $args[0]['nested'][0] = 'changed';
    unset($args, $attribute);
    gc_collect_cycles();
}
$attribute = (new ReflectionFunction('attributedArrayLifetime'))->getAttributes()[0];
var_dump($attribute->getArguments(), $attribute->newInstance()->values);
