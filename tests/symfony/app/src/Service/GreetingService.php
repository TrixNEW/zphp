<?php

namespace App\Service;

final class GreetingService
{
    public function greet(string $name): string
    {
        return "Hello, $name, from compiled Symfony";
    }
}
