<?php

// string functions and reordered args
assert(substr(string: "hello world", offset: 6) === "world");
assert(substr(offset: 0, length: 3, string: "hello") === "hel");
assert(str_replace(replace: "Y", search: "X", subject: "aXbXc") === "aYbYc");
assert(str_ireplace(replace: "Y", search: "x", subject: "aXbXc") === "aYbYc");
assert(implode(array: [1, 2, 3], separator: "-") === "1-2-3");
assert(str_pad(pad_string: ".", length: 5, string: "hi") === "hi...");

// array and search functions
assert(in_array(strict: true, haystack: ["a", "b", "c"], needle: "b") === true);
assert(in_array(needle: "d", haystack: ["a", "b", "c"]) === false);
assert(array_keys(array: ["a" => 1, "b" => 2]) === ["a", "b"]);
assert(array_keys(strict: true, filter_value: 1, array: [1, "1", 2]) === [0]);
assert(array_values(array: ["x" => 10, "y" => 20]) === [10, 20]);
assert(array_flip(array: ["a" => 1, "b" => 2]) === [1 => "a", 2 => "b"]);

// encoding, json, and math
assert(json_encode(value: ["a" => 1]) === '{"a":1}');
assert(base64_encode(string: "test") === "dGVzdA==");
assert(base64_decode(strict: true, string: "dGVzdA==") === "test");
assert(bin2hex(string: "ABC") === "414243");
assert(hex2bin(string: "414243") === "ABC");
assert(round(precision: 2, num: 3.14159) === 3.14);

// url, multibyte, and path functions
assert(parse_url(component: PHP_URL_PATH, url: "https://example.com/api/v1") === "/api/v1");
assert(http_build_query(numeric_prefix: "num_", data: ["a" => 1, 2 => "b"]) === "a=1&num_2=b");
assert(mb_strlen(encoding: "UTF-8", string: "café") === 4);
assert(mb_substr(encoding: "UTF-8", length: 2, start: 1, string: "café") === "af");
assert(pathinfo(flags: PATHINFO_EXTENSION, path: "/path/to/file.php") === "php");
assert(basename(suffix: ".txt", path: "/path/to/note.txt") === "note");

$threw = false;
try {
    str_replace(invalid_name: "test", replace: "b", search: "a", subject: "a");
} catch (Error $e) {
    $threw = true;
}
assert($threw === true, "Passing unknown named argument must throw an Error");

echo "All named argument tests passed!\n";
