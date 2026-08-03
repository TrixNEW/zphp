<?php declare(strict_types=1);
namespace App;
use Acme\Greeter\Greeter;
final class Runner { public static function run(): string { return Greeter::message('Composer'); } }
