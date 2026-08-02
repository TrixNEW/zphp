<?php

$dir = sys_get_temp_dir() . '/zphp_require_scope_' . getmypid();
mkdir($dir);

file_put_contents($dir . '/scope.php', <<<'PHP'
<?php
printf("inside:%s this:%s\n", $value, isset($this) ? $this->name : 'none');
$value .= '-included';
$created = 'new';
$nullValue = null;
unset($removed);
$alias =& $value;
$alias .= '-reference';
$__userVariable = 'preserved';
return 'result';
PHP);

function requireCallerScope(string $file): void
{
    $value = 'caller';
    $removed = 'yes';
    $result = include $file;
    printf(
        "after:%s created:%s null:%s removed:%s alias:%s private:%s result:%s\n",
        $value,
        $created,
        $nullValue === null ? 'null' : 'other',
        isset($removed) ? 'yes' : 'no',
        $alias,
        $__userVariable,
        $result,
    );
    $alias .= '-again';
    echo "identity:$value\n";
}

class RequireCallerScopeBox
{
    public string $name = 'box';

    public function run(string $file): void
    {
        $value = 'method';
        include $file;
        echo "method:$value created:$created\n";
    }
}

requireCallerScope($dir . '/scope.php');
(new RequireCallerScopeBox())->run($dir . '/scope.php');

unlink($dir . '/scope.php');
rmdir($dir);
