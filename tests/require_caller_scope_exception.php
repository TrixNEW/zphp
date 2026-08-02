<?php

$dir = sys_get_temp_dir() . '/zphp_require_exception_' . getmypid();
mkdir($dir);

file_put_contents($dir . '/throw.php', <<<'PHP'
<?php
$value = 'changed';
$created = 'before-throw';
throw new RuntimeException('included');
PHP);

function requireCallerScopeException(string $file): void
{
    $value = 'original';
    try {
        include $file;
    } catch (RuntimeException $error) {
        echo $error->getMessage(), "\n";
    }
    echo "$value $created\n";
}

requireCallerScopeException($dir . '/throw.php');
unlink($dir . '/throw.php');
rmdir($dir);
