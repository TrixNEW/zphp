<?php
interface DocInterface { /** interface */ const IFACE = 1; }
trait DocTrait { /** trait */ const TRAIT_VALUE = 2; }
class DocParent { /** parent */ public const VALUE = 3; /** private */ private const SECRET = 4; }
class DocChild extends DocParent implements DocInterface { use DocTrait; const PLAIN = 5; }
class DocOverride extends DocParent { const VALUE = 6; }
enum DocEnum { /** case */ case ONE; /** enum */ const VALUE = 7; }
foreach ([[DocParent::class, 'VALUE'], [DocParent::class, 'SECRET'], [DocChild::class, 'VALUE'], [DocChild::class, 'TRAIT_VALUE'], [DocChild::class, 'IFACE'], [DocChild::class, 'PLAIN'], [DocOverride::class, 'VALUE'], [DocInterface::class, 'IFACE'], [DocTrait::class, 'TRAIT_VALUE'], [DocEnum::class, 'ONE'], [DocEnum::class, 'VALUE'], [ReflectionClassConstant::class, 'IS_PUBLIC']] as [$class, $name]) {
    echo "$class::$name ";
    var_dump((new ReflectionClassConstant($class, $name))->getDocComment());
}
