<?php
// checking a static property's visibility can autoload an interface the
// calling class implements; classes the autoloader declares must not leave
// the property's class pointing at moved memory
spl_autoload_register(function ($name) {
    if ($name !== 'LazyContract') return;
    for ($i = 0; $i < 2000; $i++) eval("class Filler$i {}");
    eval('interface LazyContract {}');
});

class Base {
    protected static $count = 0;
    public protected(set) static int $limit = 1;
}

class Child extends Base implements LazyContract {
    public static function bump(): void {
        // a plain write first, so the autoload happens inside a write check
        Base::$count = 0;
        static::$count++;
        Base::$count++;
        static::$limit = 5;
    }
    public static function count(): int { return static::$count; }
}

Child::bump();
var_dump(Child::count(), Base::$limit, class_exists('Filler1999', false));
try {
    Base::$limit = 9;
} catch (Error $e) {
    echo $e->getMessage(), "\n";
}
