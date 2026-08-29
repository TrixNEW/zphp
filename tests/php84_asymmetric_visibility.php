<?php

function check(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException("FAIL: " . $message);
    }
}

function checkProperty(
    string|object $class,
    string $name,
    bool $public,
    bool $protected,
    bool $private,
    bool $protectedSet,
    bool $privateSet,
    bool $static,
    bool $readonly,
    bool $final,
    int $modifiers
): void {
    $rp = new ReflectionProperty($class, $name);

    check($rp->isPublic() === $public, "$name isPublic()");
    check($rp->isProtected() === $protected, "$name isProtected()");
    check($rp->isPrivate() === $private, "$name isPrivate()");
    check($rp->isProtectedSet() === $protectedSet, "$name isProtectedSet()");
    check($rp->isPrivateSet() === $privateSet, "$name isPrivateSet()");
    check($rp->isStatic() === $static, "$name isStatic()");
    check($rp->isReadOnly() === $readonly, "$name isReadOnly()");
    check($rp->isFinal() === $final, "$name isFinal()");
    check($rp->getModifiers() === $modifiers, "$name getModifiers()");
}

class AsymTest {
    public string $a = "a";
    public private(set) string $b = "b";
    public protected(set) string $c = "c";
    protected private(set) string $d = "d";
    private string $e = "e";
    public static string $f = "f";
    public readonly string $g;
    protected readonly string $h;
    private readonly string $i;
    public public(set) readonly string $j;
    public protected(set) readonly string $k;
    public private(set) readonly string $l;
    final protected string $m;
    private private(set) string $n;
}

checkProperty(AsymTest::class, 'a', true, false, false, false, false, false, false, false, 1);
checkProperty(AsymTest::class, 'b', true, false, false, false, true, false, false, true, 4129);
checkProperty(AsymTest::class, 'c', true, false, false, true, false, false, false, false, 2049);
checkProperty(AsymTest::class, 'd', false, true, false, false, true, false, false, true, 4130);
checkProperty(AsymTest::class, 'e', false, false, true, false, false, false, false, false, 4);
checkProperty(AsymTest::class, 'f', true, false, false, false, false, true, false, false, 17);
checkProperty(AsymTest::class, 'g', true, false, false, true, false, false, true, false, 2177);
checkProperty(AsymTest::class, 'h', false, true, false, false, false, false, true, false, 130);
checkProperty(AsymTest::class, 'i', false, false, true, false, false, false, true, false, 132);
checkProperty(AsymTest::class, 'j', true, false, false, false, false, false, true, false, 129);
checkProperty(AsymTest::class, 'k', true, false, false, true, false, false, true, false, 2177);
checkProperty(AsymTest::class, 'l', true, false, false, false, true, false, true, true, 4257);
checkProperty(AsymTest::class, 'm', false, true, false, false, false, false, false, true, 34);
checkProperty(AsymTest::class, 'n', false, false, true, false, false, false, false, false, 4);

check(!method_exists(ReflectionProperty::class, 'isPublicSet'), 'ReflectionProperty::isPublicSet() must not exist');

// Symmetric private visibility is not final and may be redeclared in a child.
class SymmetricPrivateParent {
    private private(set) string $value;
}
class SymmetricPrivateChild extends SymmetricPrivateParent {
    private string $value;
}
check(true, 'symmetric private(set) redeclaration is legal');

// private readonly is also not a final property.
class PrivateReadonlyParent {
    private readonly int $value;
}
class PrivateReadonlyChild extends PrivateReadonlyParent {
    private int $value;
}
check(true, 'private readonly redeclaration is legal');


readonly class ReadonlyPrivateClass {
    private string $value;
}
checkProperty(ReadonlyPrivateClass::class, 'value', false, false, true, false, false, false, true, false, 132);

trait AsymTrait {
    public private(set) string $traitPrivateSet = "trait";
    public readonly string $traitReadonly;
    private function traitPrivateMethod(): void {}
    final protected function traitFinalMethod(): void {}
}

class UsesAsymTrait {
    use AsymTrait;
}

checkProperty(UsesAsymTrait::class, 'traitPrivateSet', true, false, false, false, true, false, false, true, 4129);
checkProperty(UsesAsymTrait::class, 'traitReadonly', true, false, false, true, false, false, true, false, 2177);

$traitPrivate = new ReflectionMethod(UsesAsymTrait::class, 'traitPrivateMethod');
check($traitPrivate->isPrivate(), 'trait private method visibility');

$traitFinal = new ReflectionMethod(UsesAsymTrait::class, 'traitFinalMethod');
check($traitFinal->isProtected(), 'trait final method protected visibility');
check($traitFinal->isFinal(), 'trait final method isFinal()');

class PromotedAsym {
    public function __construct(
        public private(set) string $promoted,
    ) {}
}

$promoted = new ReflectionProperty(PromotedAsym::class, 'promoted');
check($promoted->isPromoted(), 'promoted property isPromoted()');
check($promoted->isPrivateSet(), 'promoted property isPrivateSet()');
check($promoted->isFinal(), 'promoted private(set) property isFinal()');
check($promoted->getModifiers() === 4129, 'promoted property getModifiers()');

class PromotedAbbreviatedAsym {
    public function __construct(
        private(set) string $promoted,
    ) {}
}

$promotedAbbreviated = new ReflectionProperty(PromotedAbbreviatedAsym::class, 'promoted');
check($promotedAbbreviated->isPromoted(), 'abbreviated promoted property isPromoted()');
check($promotedAbbreviated->isPublic(), 'abbreviated promoted property isPublic()');
check($promotedAbbreviated->isPrivateSet(), 'abbreviated promoted property isPrivateSet()');
check($promotedAbbreviated->isFinal(), 'abbreviated promoted private(set) property isFinal()');
check($promotedAbbreviated->getModifiers() === 4129, 'abbreviated promoted property getModifiers()');

$anon = new class {
    public protected(set) string $anonymous = "anonymous";
};
checkProperty($anon, 'anonymous', true, false, false, true, false, false, false, false, 2049);

check(ReflectionProperty::IS_PROTECTED_SET === 2048, 'IS_PROTECTED_SET');
check(ReflectionProperty::IS_PRIVATE_SET === 4096, 'IS_PRIVATE_SET');
check(ReflectionProperty::IS_FINAL === 32, 'IS_FINAL');
check(ReflectionProperty::IS_ABSTRACT === 64, 'IS_ABSTRACT');
check(ReflectionProperty::IS_VIRTUAL === 512, 'IS_VIRTUAL');

echo "OK\n";
