<?php
// natives check their argument count against php's signatures and throw
// ArgumentCountError with php's wording, whatever route the call takes

function attempt(string $label, callable $call): void {
    try {
        $call();
        echo "$label: no error\n";
    } catch (ArgumentCountError $e) {
        echo "$label: ", $e->getMessage(), "\n", $e->getTraceAsString(), "\n";
    }
}

attempt('too few', fn() => strlen());
attempt('too many', fn() => strlen('a', 'b'));
attempt('at least', fn() => sprintf());
attempt('at most', fn() => explode(',', 'a,b', 2, 'x'));
attempt('variadic accepts many', fn() => max(1, 2, 3, 4, 5, 6));
attempt('optional omitted', fn() => str_pad('a', 3));
attempt('method', fn() => (new ArrayObject([]))->offsetGet());
attempt('method too many', fn() => (new ArrayObject([1]))->count(1));
attempt('first-class callable', function () { $f = str_repeat(...); $f('a'); });
attempt('method callable', function () { $f = (new ArrayObject([]))->offsetGet(...); $f(); });
attempt('call_user_func', fn() => call_user_func('str_repeat', 'a'));
attempt('call_user_func_array', fn() => call_user_func_array([new ArrayObject([]), 'offsetSet'], [1]));
attempt('array_map callback', fn() => array_map('str_repeat', ['a']));
attempt('static method', fn() => DateTime::createFromFormat('Y'));
attempt('constructor', fn() => new ArrayIterator([], 0, 'extra'));
attempt('named arguments', fn() => str_pad(string: 'a', length: 3, pad_string: '-'));
