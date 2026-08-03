<?php

class Payload
{
    public function value(): string { return 'alive'; }
}

$array = [];
$payload = new Payload();
array_unshift($array, $payload);
unset($payload);
echo $array[0]->value(), "\n";
