<?php

class AsymTest {
    public string $a = "a";
    public private(set) string $b = "b";
    public protected(set) string $c = "c";
    protected private(set) string $d = "d";
    private string $e = "e";
    public static string $f = "f";
    public readonly string $g;
}

$props = ['a', 'b', 'c', 'd', 'e', 'f', 'g'];
foreach ($props as $name) {
    $rp = new ReflectionProperty(AsymTest::class, $name);
    echo $name . ': '
        . ($rp->isPublic() ? 'pub ' : '')
        . ($rp->isProtected() ? 'prot ' : '')
        . ($rp->isPrivate() ? 'priv ' : '')
        . ($rp->isProtectedSet() ? 'protSet ' : '')
        . ($rp->isPrivateSet() ? 'privSet ' : '')
        . ($rp->isStatic() ? 'static ' : '')
        . ($rp->isReadOnly() ? 'readonly ' : '')
        . 'mods=' . $rp->getModifiers()
        . "\n";
}

echo "IS_PROTECTED_SET: " . ReflectionProperty::IS_PROTECTED_SET . "\n";
echo "IS_PRIVATE_SET: " . ReflectionProperty::IS_PRIVATE_SET . "\n";
echo "IS_FINAL: " . ReflectionProperty::IS_FINAL . "\n";
echo "IS_ABSTRACT: " . ReflectionProperty::IS_ABSTRACT . "\n";
echo "IS_VIRTUAL: " . ReflectionProperty::IS_VIRTUAL . "\n";
