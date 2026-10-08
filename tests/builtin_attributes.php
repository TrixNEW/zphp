<?php
// the attribute classes php declares, with the targets they allow; #['\DelayedTargetValidation']
// (php 8.5) leaves a misplaced built-in attribute to fail at newInstance()
foreach (['DelayedTargetValidation', 'NoDiscard', 'Deprecated', 'Override', 'SensitiveParameter', 'AllowDynamicProperties', 'ReturnTypeWillChange', 'Attribute'] as $c) {
    $r = new ReflectionClass($c);
    echo $c, " final=", var_export($r->isFinal(), true), " targets=", $r->getAttributes()[0]->getArguments()[0] ?? '-', "\n";
}
#[DelayedTargetValidation] #[NoDiscard("why")] class Odd {}
foreach ((new ReflectionClass('Odd'))->getAttributes() as $attr) {
    try { $o = $attr->newInstance(); echo $attr->getName(), " ok\n"; } catch (Error $e) { echo $attr->getName(), ": ", $e->getMessage(), "\n"; }
}
#[NoDiscard("m")] function f() { return 1; }
$nd = (new ReflectionFunction('f'))->getAttributes()[0]->newInstance();
var_dump($nd->message, (new NoDiscard())->message);
var_dump(Attribute::TARGET_CONSTANT, Attribute::TARGET_ALL);

// user attributes are checked against their declared targets the same way
#[Attribute(Attribute::TARGET_METHOD)] class OnlyMethods {}
#[Attribute] class Anywhere {}
#[Attribute(Attribute::TARGET_CLASS)] class Once {}
#[OnlyMethods] #[Anywhere] #[Once] #[Once] class C { #[OnlyMethods] function m() {} }
foreach ((new ReflectionClass('C'))->getAttributes() as $a) { try { $a->newInstance(); echo $a->getName(), " ok\n"; } catch (Error $e) { echo $a->getName(), ": ", $e->getMessage(), "\n"; } }
var_dump((new ReflectionMethod('C', 'm'))->getAttributes()[0]->newInstance() instanceof OnlyMethods);
var_dump(Attribute::TARGET_CONSTANT ?? null);
