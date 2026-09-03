<?php

// inline JSON config bundled into a script via a data URI
$configUri = 'data://application/json;base64,' . base64_encode(json_encode([
    'name' => 'demo',
    'version' => '1.0.0',
    'features' => ['data-uri', 'streams'],
]));
$config = json_decode(file_get_contents($configUri), true);
echo "name: " . $config['name'] . "\n";
echo "version: " . $config['version'] . "\n";
echo "features: " . implode(', ', $config['features']) . "\n";

// load a multiline text fixture without touching disk
$lines = 'data://text/plain,first%0Asecond%0Athird%0A';
$f = fopen($lines, 'r');
while (!feof($f)) {
    $line = fgets($f);
    if ($line === false) break;
    echo "line: " . trim($line) . "\n";
}
fclose($f);

$pngHeader = file_get_contents('data://application/octet-stream;base64,iVBORw0KGgo=');
echo "header bytes: " . bin2hex($pngHeader) . "\n";

$f = fopen('data://text/plain;base64,aGVsbG8=', 'r');
echo "first read: " . fread($f, 100) . "\n";
rewind($f);
echo "after rewind: " . fread($f, 100) . "\n";
fclose($f);

$wrappers = stream_get_wrappers();
echo "data wrapper registered: " . (in_array('data', $wrappers) ? "yes" : "no") . "\n";
