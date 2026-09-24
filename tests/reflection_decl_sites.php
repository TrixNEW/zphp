<?php
require __DIR__ . '/include/decl_sites.php';

/**
 * Local class
 */
final class LocalClass
{
}

foreach (['I', 'T', 'E', 'LocalClass'] as $name) {
    $r = new ReflectionClass($name);
    echo $name, ' ', basename((string) $r->getFileName()), ' ', var_export($r->getStartLine(), true), ' ', var_export($r->getEndLine(), true), ' ', json_encode($r->getDocComment()), "\n";
}
echo var_export((new ReflectionClass('ArrayObject'))->getFileName(), true), "\n";
