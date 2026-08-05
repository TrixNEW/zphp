<?php

final class Stack implements ArrayAccess
{
    private array $items = [];

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
            return;
        }
        $this->items[$offset] = $value;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }
}

$stack = new Stack();
$result = $stack[] = 'first';
var_dump($result, $stack[0]);

$array = [];
$result = $array[] = 'second';
var_dump($result, $array);
