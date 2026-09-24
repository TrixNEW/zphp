<?php
foreach (['ReflectionClass','ReflectionObject','ReflectionEnum','ReflectionMethod','ReflectionFunction','ReflectionFunctionAbstract','ReflectionParameter','ReflectionProperty','ReflectionClassConstant','ReflectionEnumUnitCase','ReflectionEnumBackedCase','ReflectionNamedType','ReflectionUnionType','ReflectionIntersectionType','ReflectionType','ReflectionAttribute','ReflectionExtension','ReflectionZendExtension','ReflectionConstant','ReflectionGenerator','ReflectionFiber','ReflectionReference','ReflectionException','Reflection'] as $c) {
    if (!class_exists($c)) { echo "$c missing\n"; continue; }
    $i = class_implements($c); sort($i);
    $p = class_parents($c);
    echo $c, ': ', implode(',', $i), ' < ', implode(',', $p), "\n";
}
enum Suit: string { case Hearts = 'H'; const Wild = self::Hearts; }
class K { const A = 1; }
foreach ([['Suit', 'Hearts'], ['Suit', 'Wild'], ['Suit', 'Nope'], ['K', 'A']] as [$c, $n]) {
    try { $r = new ReflectionEnumBackedCase($c, $n); echo $r->getName(), ' ', $r->getBackingValue(), "\n"; }
    catch (ReflectionException $e) { echo get_class($e), ': ', $e->getMessage(), "\n"; }
    try { $r = new ReflectionClassConstant($c, $n); echo $r->getName(), "\n"; }
    catch (ReflectionException $e) { echo get_class($e), ': ', $e->getMessage(), "\n"; }
}
$case = (new ReflectionEnum('Suit'))->getCase('Hearts');
var_dump($case instanceof ReflectionClassConstant, $case instanceof Reflector, $case->isPublic(), $case->isEnumCase(), $case->getDeclaringClass()->getName());
try { new ReflectionZendExtension("opcache"); } catch (ReflectionException $e) { echo $e->getMessage(), "
"; }
