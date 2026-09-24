<?php
interface HasLimit
{
    const LIMIT = 10;
}

trait Tagged
{
    public const TAG = 'tagged';
    public static $TAG = 'static tag';
}

enum Suit: string
{
    case Hearts = 'H';
    const Wild = self::Hearts;
}

final class Logic implements HasLimit
{
    use Tagged;

    private const YES = 3;
    public const NAMES = ['yes' => self::YES];
    private static array $registry = [];
    private static ?self $YES = null;
    public static $LIMIT = 'static limit';

    public function __construct(public int $value)
    {
    }

    public static function yes(): self
    {
        return self::$YES ??= self::$registry[self::YES] ??= new self(self::YES);
    }

    public static function constByName(string $name)
    {
        return self::{$name};
    }
}

$yes = Logic::yes();
echo $yes->value, ' ', var_export($yes === Logic::yes(), true), "\n";
echo Logic::LIMIT, ' ', Logic::$LIMIT, ' ', Logic::TAG, ' ', Logic::$TAG, "\n";
echo Suit::Hearts->value, ' ', Suit::Wild->name, "\n";
echo Logic::constByName('LIMIT'), ' ', Logic::{'TAG'}, "\n";
$class = 'Logic';
$object = $yes;
$name = 'LIMIT';
echo $class::LIMIT, ' ', $object::LIMIT, ' ', $class::$LIMIT, ' ', $class::{$name}, ' ', Logic::${'LIMIT'}, "\n";
var_dump(defined('Logic::LIMIT'), defined('Logic::NOPE'), constant('Suit::Hearts') === Suit::Hearts);
$r = new ReflectionClass('Logic');
var_dump(array_keys($r->getConstants()), array_keys($r->getStaticProperties()));
var_dump($r->hasConstant('YES'), $r->getConstant('TAG'), $r->getStaticPropertyValue('LIMIT'));
var_dump(array_map(fn($c) => $c->name, Suit::cases()));
try {
    echo Logic::NOPE;
} catch (Error $e) {
    echo get_class($e), ': ', $e->getMessage(), "\n";
}
try {
    echo $class::{'MISSING'};
} catch (Error $e) {
    echo get_class($e), ': ', $e->getMessage(), "\n";
}

class Typed
{
    const int X = 1;
    public static string $X = 's';
}
$c = new ReflectionClassConstant('Typed', 'X');
$p = new ReflectionProperty('Typed', 'X');
var_dump((string) $c->getType(), $c->hasType(), (string) $p->getType(), Typed::X, Typed::$X);
