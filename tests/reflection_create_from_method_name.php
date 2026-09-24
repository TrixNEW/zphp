<?php
class Greeter
{
    public function hello(string $name): string
    {
        return "hi $name";
    }
}

class MyMethod extends ReflectionMethod
{
}

$m = ReflectionMethod::createFromMethodName('Greeter::hello');
echo get_class($m), ' ', $m->getName(), ' ', $m->class, ' ', $m->getNumberOfParameters(), "\n";
$mine = MyMethod::createFromMethodName('Greeter::hello');
echo get_class($mine), "\n";
foreach (['Nope', 'Greeter::nope', 'ArrayObject::count'] as $name) {
    try {
        $r = ReflectionMethod::createFromMethodName($name);
        echo $r->class, '::', $r->name, "\n";
    } catch (ReflectionException $e) {
        echo get_class($e), ': ', $e->getMessage(), "\n";
    }
}
