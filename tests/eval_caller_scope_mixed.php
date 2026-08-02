<?php

$dir = sys_get_temp_dir() . '/zphp_eval_mixed_' . getmypid();
mkdir($dir);

file_put_contents($dir . '/from-eval.php', <<<'PHP'
<?php
$value .= '-include';
$fromInclude = 'yes';
PHP);

file_put_contents($dir . '/to-eval.php', <<<'PHP'
<?php
$value .= '-outer';
eval('$value .= "-eval"; $fromEval = "yes";');
PHP);

function evalThenInclude(string $file): void
{
    $value = 'eval';
    eval('include $file; $value .= "-after";');
    echo "$value $fromInclude\n";
}

function includeThenEval(string $file): void
{
    $value = 'include';
    include $file;
    echo "$value $fromEval\n";
}

evalThenInclude($dir . '/from-eval.php');
includeThenEval($dir . '/to-eval.php');

unlink($dir . '/from-eval.php');
unlink($dir . '/to-eval.php');
rmdir($dir);
