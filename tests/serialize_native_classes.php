<?php
class Info extends SplFileInfo {}
class InfoWithMethods extends SplFileInfo {
    public function __serialize(): array { return []; }
    public function __unserialize(array $data): void {}
}
class Doc extends DOMDocument {}
class DocWithMethods extends DOMDocument {
    public function __serialize(): array { return ['version' => 1]; }
    public function __unserialize(array $data): void {}
}

$values = [
    'pdo' => fn() => new PDO('sqlite::memory:'),
    'simplexml' => fn() => simplexml_load_string('<a/>'),
    'dom' => fn() => new DOMDocument(),
    'dom subclass' => fn() => new Doc(),
    'dom subclass with methods' => fn() => new DocWithMethods(),
    'spl subclass' => fn() => new Info(__FILE__),
    'spl subclass with methods' => fn() => new InfoWithMethods(__FILE__),
    'reflection' => fn() => new ReflectionClass('stdClass'),
    'nested' => fn() => ['ok' => 1, 'bad' => new SplFileInfo('x')],
    'gmp' => fn() => [gmp_init(42), gmp_init('-123456789012345678901234567890')],
];
foreach ($values as $name => $make) {
    try {
        echo $name, ': ', serialize($make()), "\n";
    } catch (Throwable $e) {
        echo $name, ': ', get_class($e), ': ', $e->getMessage(), "\n";
    }
}

foreach (['PDO', 'SplFileInfo', 'Info', 'DOMDocument', 'DocWithMethods'] as $class) {
    try {
        echo $class, ': ', get_class(unserialize(sprintf('O:%d:"%s":0:{}', strlen($class), $class))), "\n";
    } catch (Throwable $e) {
        echo $class, ': ', get_class($e), ': ', $e->getMessage(), "\n";
    }
}

$g = unserialize(serialize(gmp_init(255)));
echo gmp_strval($g), ' ', gmp_strval(gmp_add($g, 1)), "\n";
print_r(gmp_init(10));
echo "\n";
foreach (['O:3:"GMP":1:{i:0;s:2:"zz";}', 'O:3:"GMP":0:{}'] as $bad) {
    try { unserialize($bad); } catch (Exception $e) { echo $e->getMessage(), "\n"; }
}
var_export([fopen(__FILE__, 'r'), 1]);
echo "\n";
