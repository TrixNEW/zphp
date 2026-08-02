<?php

class ArrayArgumentLifetimeItem {}

class ArrayArgumentLifetimeHolder
{
    public array $items = [];

    public function __construct()
    {
        $this->items[] = new ArrayArgumentLifetimeItem();
    }

    public function result(): array
    {
        return $this->items;
    }
}

function arrayArgumentLifetimeProduce(): array
{
    $holder = new ArrayArgumentLifetimeHolder();
    return $holder->result();
}

function arrayArgumentLifetimeLater(): int
{
    $temporary = new stdClass();
    return 1;
}

function arrayArgumentLifetimeConsume(array $items, int $unused): void
{
    echo $items[0]::class, "\n";
}

arrayArgumentLifetimeConsume(
    arrayArgumentLifetimeProduce(),
    arrayArgumentLifetimeLater(),
);
