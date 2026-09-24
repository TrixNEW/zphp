<?php
const TOP_A = 1, TOP_B = TOP_A + 1;

class Limits
{
    const MIN = 1, MAX = 10;
    private const Services = 'services', Parameters = 'parameters';
    final public const int WIDTH = 80, HEIGHT = 24;
    protected const LIST = [self::MIN, self::MAX], SUM = self::MIN + self::MAX;

    public static function names(): array
    {
        return [self::Services, self::Parameters, static::SUM];
    }
}

interface Codes
{
    const OK = 200, NOT_FOUND = 404;
}

enum Size: int
{
    const DEFAULT = self::Medium, ALL = [self::Small, self::Medium];
    case Small = 1;
    case Medium = 2;
}

trait Tagged
{
    public const PREFIX = '#', SUFFIX = ';';
}

final class Tag
{
    use Tagged;
}

var_dump(TOP_A, TOP_B, Limits::MIN, Limits::MAX, Limits::WIDTH, Limits::HEIGHT, Limits::names());
var_dump(Codes::NOT_FOUND, Size::DEFAULT, count(Size::ALL), Tag::PREFIX . Tag::SUFFIX);
$r = new ReflectionClass(Limits::class);
foreach ($r->getReflectionConstants() as $c) {
    echo $c->getName(), ' ', $c->isPrivate() ? 'private' : ($c->isProtected() ? 'protected' : 'public'), $c->isFinal() ? ' final' : '', "\n";
}
