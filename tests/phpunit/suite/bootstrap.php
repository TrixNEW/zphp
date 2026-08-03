<?php declare(strict_types=1);
interface FixtureService { public function fetch(string $key): string; }
final class FixtureCalculator { public function add(int $a, int $b): int { return $a + $b; } }
