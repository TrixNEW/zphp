<?php

spl_autoload_register(function (string $class): void {
    if ($class === 'LazyParent') {
        eval('class LazyParent { public static function inherited(): string { return "loaded"; } }');
    }
});

class EagerChild extends LazyParent {}

var_dump(method_exists(EagerChild::class, 'inherited'));
echo EagerChild::inherited(), "\n";
