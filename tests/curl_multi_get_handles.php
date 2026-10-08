<?php
// php 8.5's curl_multi_get_handles(), and the finished handle in
// curl_multi_info_read(), over local file:// transfers
$dir = sys_get_temp_dir() . "/zphp_curl_multi_" . getmypid();
mkdir($dir);
file_put_contents("$dir/a.txt", "alpha");
file_put_contents("$dir/b.txt", "beta");
$m = curl_multi_init();
$a = curl_init("file://$dir/a.txt");
$b = curl_init("file://$dir/b.txt");
curl_setopt($a, CURLOPT_RETURNTRANSFER, true);
curl_setopt($b, CURLOPT_RETURNTRANSFER, true);
var_dump(curl_multi_get_handles($m));
var_dump(curl_multi_add_handle($m, $a), curl_multi_add_handle($m, $a), curl_multi_add_handle($m, $b));
$handles = curl_multi_get_handles($m);
var_dump(count($handles), $handles[0] === $a, $handles[1] === $b);
do {
    $status = curl_multi_exec($m, $running);
    if ($running) curl_multi_select($m, 0.1);
} while ($running && $status === CURLM_OK);
$done = [];
while ($info = curl_multi_info_read($m)) {
    $done[] = ($info['handle'] === $a ? 'a' : ($info['handle'] === $b ? 'b' : '?')) . '=' . curl_multi_getcontent($info['handle']);
}
sort($done);
var_dump($done);
curl_multi_remove_handle($m, $a);
var_dump(array_keys(curl_multi_get_handles($m)), curl_multi_get_handles($m)[0] === $b);
curl_multi_remove_handle($m, $b);
var_dump(curl_multi_get_handles($m));
unlink("$dir/a.txt"); unlink("$dir/b.txt"); rmdir($dir);
