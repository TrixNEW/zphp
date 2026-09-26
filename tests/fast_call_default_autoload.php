<?php
// a parameter default whose class cannot be loaded throws Error; once the
// autoloader provides it, the same call site resolves the default normally
// and releases the receiver and arguments as a call without defaults does

$attempts = 0;
spl_autoload_register(function (string $class) use (&$attempts) {
    if (++$attempts === 1) return;
    $parts = [];
    for ($i = 0; $i < 4; $i++) $parts[] = str_repeat($class, 2) . $i;
    $joined = implode('/', $parts);
    strlen($joined . '!');
    eval("class $class { const MODE = " . strlen($class) . "; }");
});

class Registry {
    function __construct(public string $name) {}
    function get(string $id, int $mode = LateMode::MODE) { return "{$this->name} $id:$mode"; }
    function __destruct() { echo "released {$this->name}\n"; }
}

function lookup(Registry $registry, string $id) {
    return $registry->get($id);
}

for ($i = 0; $i < 4; $i++) {
    try {
        $out = lookup(new Registry("r$i"), 'id' . $i);
        echo $out, "\n";
    } catch (Error $e) {
        echo get_class($e), ": ", $e->getMessage(), "\n";
    }
}
$held = 'held' . str_repeat('v', 3);
$out = lookup(new Registry('last'), $held);
echo $out, " ", $held, "\n";
echo "done\n";
