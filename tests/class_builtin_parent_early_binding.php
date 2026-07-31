<?php
// A top-level class with an already-bound built-in parent is available before its declaration.

$items = new EarlyArrayObject(['a', 'b']);
echo get_class($items), ':', get_parent_class($items), ':', count($items), "\n";

$exception = new EarlyRuntimeException('ready');
echo get_parent_class($exception), ':', $exception->getMessage(), "\n";

class EarlyArrayObject extends ArrayObject {}
class EarlyRuntimeException extends RuntimeException {}
