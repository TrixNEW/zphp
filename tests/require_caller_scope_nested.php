<?php

$dir = sys_get_temp_dir() . '/zphp_require_nested_' . getmypid();
mkdir($dir);

file_put_contents($dir . '/inner.php', <<<'PHP'
<?php
$count++;
$list[] = 'inner';
${$dynamicName} = 'dynamic';
$reference .= '-inner';
unset($removeInner);
PHP);

file_put_contents($dir . '/outer.php', <<<'PHP'
<?php
$count++;
$list[] = 'outer';
$reference =& $value;
include __DIR__ . '/inner.php';
echo "during:$count " . implode(',', $list) . " $value $dynamicValue " . (isset($removeInner) ? 'set' : 'unset') . "\n";
PHP);

function requireCallerScopeNested(string $file): void
{
    $count = 1;
    $list = ['caller'];
    $value = 'value';
    $dynamicName = 'dynamicValue';
    $removeInner = true;
    include $file;
    echo "after:$count " . implode(',', $list) . " $value $dynamicValue " . (isset($removeInner) ? 'set' : 'unset') . "\n";
}

requireCallerScopeNested($dir . '/outer.php');
unlink($dir . '/inner.php');
unlink($dir . '/outer.php');
rmdir($dir);
