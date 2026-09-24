<?php
spl_autoload_register(function (string $class) {
    echo "autoload $class\n";
    $file = __DIR__ . '/include/lazy_defaults/' . substr($class, 4) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

$v = new LazyValidator();
var_dump($v->mask(), $v->flags, $v->extra);
$c = new LazyChild();
var_dump($c->mask());
var_dump(get_class_vars('LazyBase'));
$prop = new ReflectionProperty('LazyBase', 'mask');
var_dump($prop->getDefaultValue());
var_dump((new ReflectionClass('LazyChild'))->getDefaultProperties());

class UsesMissing
{
    public $value = MISSING_CONSTANT_XYZ;
}
echo "declared\n";
try {
    new UsesMissing();
} catch (Error $e) {
    echo get_class($e), ': ', $e->getMessage(), "\n";
}

function makeMissing()
{
    return new UsesMissing();
}
try {
    makeMissing();
} catch (Error $e) {
    echo $e->getLine(), ' ', count($e->getTrace()), ' ', $e->getTrace()[0]['function'], "\n";
}
