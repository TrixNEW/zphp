<?php

function evalCallerScopeException(): void
{
    $value = 'original';
    try {
        eval('$value = "changed"; $created = "before-throw"; throw new RuntimeException("evaluated");');
    } catch (RuntimeException $error) {
        echo $error->getMessage(), "\n";
    }
    echo "$value $created\n";
}

evalCallerScopeException();
