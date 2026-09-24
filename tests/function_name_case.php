<?php
namespace App\Util {
    function Format(string $s): string { return "[$s]"; }
    function inner(): string { return STRTOUPPER("ns fallback"); }
}

namespace {
    function MyHelper(int $n): int { return $n * 2; }

    echo STRLEN("abc"), " ", StrLen("ab"), " ", \STRTOLOWER("MiXeD"), "\n";
    echo myhelper(2), " ", MYHELPER(3), " ", \myHelper(4), "\n";
    echo App\Util\format("x"), " ", \APP\UTIL\FORMAT("y"), " ", app\util\Inner(), "\n";

    $f = 'STRREV';
    echo $f("abc"), " ", call_user_func('MyHelper', 5), " ", call_user_func('myhelper', 6), "\n";
    echo implode(",", array_map('StrToUpper', ['a', 'b'])), " ", implode(",", array_map('MYHELPER', [1, 2])), "\n";
    var_dump(function_exists('STRLEN'), function_exists('myhelper'), function_exists('\App\Util\FORMAT'), function_exists('nope'));
    var_dump(is_callable('STRLEN'), is_callable('MYHELPER'), is_callable('App\Util\format'), is_callable('nope'));
    $fc = STRTOUPPER(...);
    echo $fc("first class"), " ", (MyHelper(...))(7), "\n";
    echo (new ReflectionFunction('STRLEN'))->getName(), " ", (new ReflectionFunction('myhelper'))->getName(), "\n";
    try { UNDEFINED_THING(); } catch (Error $e) { echo $e->getMessage(), "\n"; }
}
