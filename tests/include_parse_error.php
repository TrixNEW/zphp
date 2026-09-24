<?php
$e = new ParseError('x');
var_dump(get_parent_class($e), get_parent_class(get_parent_class($e)), $e instanceof Error);

try {
    eval('function ( {');
} catch (ParseError $e) {
    echo 'eval: ', get_class($e), "\n";
}

try {
    include __DIR__ . '/include/syntax_error.php';
} catch (ParseError $e) {
    echo 'include: ', $e->getMessage(), ' @ ', basename($e->getFile()), ':', $e->getLine(), "\n";
}

try {
    require __DIR__ . '/include/syntax_error.php';
} catch (CompileError $e) {
    echo 'require: ', get_class($e), "\n";
}

// an autoloader that fails to parse raises through the function that triggered it
spl_autoload_register(function (string $name): void {
    eval('inteface ' . $name . ' {}');
});
try {
    interface_exists('BrokenInterface');
} catch (ParseError $e) {
    echo 'autoload: ', get_class($e), ' line ', $e->getLine(), ' in eval: ', var_export(str_ends_with($e->getFile(), "eval()'d code"), true), "\n";
}
try {
    class_exists('BrokenClass');
} catch (ParseError $e) {
    echo 'class_exists: ', get_class($e), "\n";
}

echo "still running\n";
