<?php
$classes = ['ReflectionClass','ReflectionMethod','ReflectionFunction','ReflectionFunctionAbstract','ReflectionProperty','ReflectionClassConstant','ReflectionAttribute','ReflectionParameter','ReflectionEnum','ReflectionEnumUnitCase','ReflectionEnumBackedCase','ReflectionObject','ReflectionNamedType','ReflectionReference','ReflectionConstant','ReflectionExtension','ReflectionGenerator','ReflectionFiber','ReflectionIntersectionType','ReflectionUnionType','ReflectionType','ReflectionZendExtension','Reflection'];
foreach ($classes as $c) {
    $consts = (new ReflectionClass($c))->getConstants();
    ksort($consts);
    foreach ($consts as $k => $v) echo "$c::$k = ", var_export($v, true), "\n";
}
