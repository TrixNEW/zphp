<?php

echo function_exists("http_get_last_response_headers") ? "y" : "n", "\n";
echo function_exists("http_clear_last_response_headers") ? "y" : "n", "\n";

// initial state without requests
$initial = http_get_last_response_headers();
var_dump($initial === null);

// clear headers return value
$clear_result = http_clear_last_response_headers();
var_dump($clear_result === null);

// state after clear
$after_clear = http_get_last_response_headers();
var_dump($after_clear === null);

// repeated clear calls
http_clear_last_response_headers();
http_clear_last_response_headers();
var_dump(http_get_last_response_headers() === null);

// failed request resets headers to null
$bad = @file_get_contents("http://127.0.0.1:1/nope");
var_dump($bad === false);
var_dump(http_get_last_response_headers() === null);

// reflection parameter counts
$rf_get = new ReflectionFunction("http_get_last_response_headers");
var_dump($rf_get->getNumberOfParameters());
var_dump($rf_get->getNumberOfRequiredParameters());

$rf_clear = new ReflectionFunction("http_clear_last_response_headers");
var_dump($rf_clear->getNumberOfParameters());
var_dump($rf_clear->getNumberOfRequiredParameters());
