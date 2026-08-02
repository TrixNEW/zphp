<?php

spl_autoload_register(function (string $class): void {
    if ($class === 'LazyParent') {
        eval('class LazyParent {}');
    } elseif ($class === 'LazyChild') {
        eval('class LazyChild extends LazyParent {}');
    } elseif ($class === 'LazyContract') {
        eval('interface LazyContract {}');
    } elseif ($class === 'LazyImplementation') {
        eval('class LazyImplementation implements LazyContract {}');
    }
});

var_dump(is_subclass_of('LazyChild', 'LazyParent'));
var_dump(is_subclass_of('LazyImplementation', 'LazyContract'));
var_dump(is_subclass_of('LazyChild', 'MissingParent'));
