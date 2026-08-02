<?php

class MixedCallMethods
{
    public function setLength(int $value): string { return "public:$value"; }
    private function hiddenCase(): string { return 'private'; }
    public function callPrivate(): string { return $this->HIDDENCASE(); }
    public static function staticMethod(): string { return 'static'; }
}

$object = new MixedCallMethods();
echo $object->setlength(3), "\n";
echo $object->SETLENGTH(4), "\n";
echo $object->callPrivate(), "\n";
echo MixedCallMethods::STATICMETHOD(), "\n";
