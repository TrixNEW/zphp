<?php

function tokenSummary(string $source): array
{
    $wanted = [T_NAMESPACE, T_USE, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE];
    $result = [];
    foreach (token_get_all($source, TOKEN_PARSE) as $token) {
        if (is_array($token) && in_array($token[0], $wanted, true)) {
            $result[] = token_name($token[0]) . ':' . $token[1];
        }
    }
    return $result;
}

var_dump(tokenSummary('<?php namespace App\\Analysis; use Vendor\\Package\\Rule; new \\Vendor\\Tool; new namespace\\LocalTool;'));

$name = null;
var_dump(is_callable(['MissingClass', 'run'], true, $name), $name);

interface ParentContract {}
interface ChildContract extends ParentContract {}
var_dump(class_implements(ChildContract::class));

final class ReflectionFixture
{
    public function run(string $value): string
    {
        return $value;
    }
}

$method = ReflectionMethod::createFromMethodName(ReflectionFixture::class . '::run');
var_dump($method instanceof Reflector, $method->getName(), $method->getDeclaringClass()->getName());
$parameter = $method->getParameters()[0];
var_dump($parameter instanceof Reflector, $parameter->getName());
