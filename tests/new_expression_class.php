<?php
class Point
{
    public function __construct(public int $x = 0, public int $y = 0) {}
}

class Factory
{
    public function __construct(private string $class) {}
    public function getClass(): string { return $this->class; }
}

$factory = new Factory(Point::class);
$names = ['p' => 'Point'];
$dynamic = 'Point';

echo json_encode(new ($factory->getClass())(1, 2)), "\n";
echo json_encode(new ('Po' . 'int')), "\n";
echo json_encode(new ($names['p'])(y: 5)), "\n";
echo json_encode(new $dynamic(y: 7, x: 3)), "\n";
echo json_encode(new $dynamic(...['y' => 9])), "\n";
echo (new ($factory->getClass()))->x, "\n";
foreach ([Point::class, ArrayObject::class] as $c) {
    echo get_class(new ($c)), "\n";
}
echo json_encode((new ('ArrayObject')(flags: ArrayObject::ARRAY_AS_PROPS, array: ['k' => 1]))->getArrayCopy()), "\n";
try {
    new $dynamic(z: 1);
} catch (Error $e) {
    echo get_class($e), ': ', $e->getMessage(), "\n";
}
