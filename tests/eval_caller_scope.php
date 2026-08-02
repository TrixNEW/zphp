<?php

function evalCallerScope(): void
{
    $value = 'caller';
    $removed = 'yes';
    $code = <<<'PHP'
printf("inside:%s this:%s\n", $value, isset($this) ? 'yes' : 'none');
$value .= '-evaluated';
$created = 'new';
$nullValue = null;
unset($removed);
$alias =& $value;
$alias .= '-reference';
return 'result';
PHP;
    $result = eval($code);
    printf(
        "after:%s created:%s null:%s removed:%s alias:%s result:%s\n",
        $value,
        $created,
        $nullValue === null ? 'null' : 'other',
        isset($removed) ? 'yes' : 'no',
        $alias,
        $result,
    );
    $alias .= '-again';
    echo "identity:$value\n";
}

class EvalCallerScopeBox
{
    public string $name = 'box';

    public function run(): void
    {
        $value = 'method';
        eval('$value .= "-evaluated"; $created = $this->name;');
        echo "method:$value created:$created\n";
    }
}

evalCallerScope();
(new EvalCallerScopeBox())->run();
