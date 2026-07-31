<?php
// ReflectionProperty raw accessors added in PHP 8.4/8.5.

class RawValueFixture {
    private string $value = 'initial';
}

$object = new RawValueFixture();
$property = new ReflectionProperty(RawValueFixture::class, 'value');
var_dump($property->getRawValue($object));
$property->setRawValue($object, 'raw');
var_dump($property->getRawValue($object));
$property->setRawValueWithoutLazyInitialization($object, 'without-lazy');
var_dump($property->getValue($object));
