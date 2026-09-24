<?php
function describe(ReflectionFunctionAbstract $f): void {
    echo $f->getName(), ': ', $f->getNumberOfParameters(), ' params, ', $f->getNumberOfRequiredParameters(), ' required';
    echo $f->isVariadic() ? ', variadic' : '';
    echo $f->hasReturnType() ? ', returns ' . $f->getReturnType() : ', no return type';
    if ($f instanceof ReflectionMethod && $f->hasTentativeReturnType()) echo ', tentative ', $f->getTentativeReturnType();
    echo "\n";
    foreach ($f->getParameters() as $p) {
        echo '  #', $p->getPosition(), ' $', $p->getName();
        echo $p->hasType() ? ' ' . $p->getType() : '';
        echo $p->isOptional() ? ' optional' : '';
        echo $p->isPassedByReference() ? ' by-ref' : '';
        echo $p->isVariadic() ? ' variadic' : '';
        echo $p->allowsNull() ? ' nullable' : '';
        if ($p->isDefaultValueAvailable()) {
            echo ' = ', var_export($p->getDefaultValue(), true);
            if ($p->isDefaultValueConstant()) echo ' (', $p->getDefaultValueConstantName(), ')';
        }
        echo "\n";
    }
}

foreach (['strlen', 'str_pad', 'json_decode', 'preg_match', 'sprintf', 'mt_rand', 'array_slice', 'htmlspecialchars', 'STRTOUPPER'] as $fn) {
    describe(new ReflectionFunction($fn));
}
describe(new ReflectionMethod('DateTime', 'format'));
describe(new ReflectionMethod('ArrayObject', 'offsetGet'));
describe(new ReflectionMethod('ArrayIterator', '__construct'));

$p = new ReflectionParameter('str_pad', 'pad_type');
echo $p->getName(), ' ', $p->getPosition(), ' ', $p->getDefaultValue(), "\n";
$p = new ReflectionParameter(['DateTime', 'format'], 0);
echo $p->getName(), ' ', $p->getType(), "\n";
var_dump((new ReflectionFunction('strlen'))->isInternal());
