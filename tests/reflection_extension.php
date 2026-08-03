<?php

$extension = new ReflectionExtension('json');
var_dump($extension->getName(), is_string($extension->getVersion()));
var_dump($extension->isPersistent(), $extension->isTemporary());
var_dump(is_array($extension->getFunctions()), is_array($extension->getConstants()));
ob_start();
var_dump($extension->info());
$info = ob_get_clean();
var_dump($info !== '');
try {
    new ReflectionExtension('missing_extension');
} catch (ReflectionException $error) {
    echo $error->getMessage(), "\n";
}
