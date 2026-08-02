<?php

function evalCallerScopeNested(): void
{
    $count = 1;
    $list = ['caller'];
    $value = 'value';
    $dynamicName = 'dynamicValue';
    $removeInner = true;
    eval(<<<'PHP'
$count++;
$list[] = 'outer';
$reference =& $value;
eval('$count++; $list[] = "inner"; ${$dynamicName} = "dynamic"; $reference .= "-inner"; unset($removeInner);');
echo "during:$count " . implode(',', $list) . " $value $dynamicValue " . (isset($removeInner) ? 'set' : 'unset') . "\n";
PHP);
    echo "after:$count " . implode(',', $list) . " $value $dynamicValue " . (isset($removeInner) ? 'set' : 'unset') . "\n";
}

evalCallerScopeNested();
