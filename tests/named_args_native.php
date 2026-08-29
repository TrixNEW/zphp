<?php

// named arguments for native/stdlib functions

echo substr(string: "hello world", offset: 6) . "\n";
echo substr(offset: 0, string: "hello", length: 3) . "\n";

echo in_array(needle: "b", haystack: ["a", "b", "c"]) ? "found" : "missing";
echo "\n";

echo str_replace(search: "X", replace: "Y", subject: "aXbXc") . "\n";
echo str_ireplace(search: "x", replace: "Y", subject: "aXbXc") . "\n";

echo implode(separator: "-", array: [1, 2, 3]) . "\n";

echo round(num: 3.14159, precision: 2) . "\n";

echo str_pad(string: "hi", length: 5, pad_string: ".") . "\n";

echo json_encode(value: ["a" => 1]) . "\n";

echo json_encode(array_keys(array: ["a" => 1, "b" => 2])) . "\n";
echo json_encode(array_values(array: ["x" => 10, "y" => 20])) . "\n";
echo json_encode(array_flip(array: ["a" => 1, "b" => 2])) . "\n";

echo base64_encode(string: "test") . "\n";
echo base64_decode(strict: true, string: "dGVzdA==") . "\n";
echo bin2hex(string: "ABC") . "\n";
echo hex2bin(string: "414243") . "\n";

echo parse_url(component: PHP_URL_PATH, url: "https://example.com/api/v1") . "\n";
echo http_build_query(numeric_prefix: "num_", data: ["a" => 1, 2 => "b"]) . "\n";

echo mb_strlen(encoding: "UTF-8", string: "café") . "\n";
echo mb_substr(encoding: "UTF-8", length: 2, start: 1, string: "café") . "\n";

echo pathinfo(flags: PATHINFO_EXTENSION, path: "/path/to/file.php") . "\n";
echo basename(suffix: ".txt", path: "/path/to/note.txt") . "\n";
