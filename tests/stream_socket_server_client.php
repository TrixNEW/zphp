<?php
$server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
var_dump($errno, $errstr, is_resource($server));
$address = stream_socket_get_name($server, false);
var_dump((bool) preg_match('/^127\.0\.0\.1:\d+$/', $address), stream_socket_get_name($server, true));
stream_set_blocking($server, false);
$meta = stream_get_meta_data($server);
var_dump($meta['blocked'], $meta['stream_type'], $meta['seekable'], array_key_exists('wrapper_type', $meta));

$client = stream_socket_client("tcp://$address", $errno, $errstr, 5, STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT);
var_dump($errno, is_resource($client));
$write = [$client];
$read = $except = null;
var_dump(stream_select($read, $write, $except, 5));
var_dump(stream_socket_get_name($client, true) === $address);

$read = [$server];
$write = $except = null;
var_dump(stream_select($read, $write, $except, 5));
$peer = null;
$conn = stream_socket_accept($server, 5, $peer);
var_dump(is_resource($conn), stream_socket_get_name($client, false) === $peer, stream_get_meta_data($conn)['blocked']);
stream_set_blocking($conn, true);

fwrite($client, "hello\n");
var_dump(fgets($conn));
stream_set_blocking($conn, false);
var_dump(fread($conn, 100), feof($conn));
fwrite($conn, "partial");
stream_set_blocking($client, true);
var_dump(fread($client, 100), feof($client));
stream_socket_shutdown($client, STREAM_SHUT_WR);
$read = [$conn];
$write = $except = null;
stream_select($read, $write, $except, 5);
var_dump(stream_get_contents($conn), feof($conn));
fclose($conn);
fclose($client);

var_dump(@stream_socket_client('tcp://127.0.0.1:1', $errno, $errstr, 2), $errno > 0, $errstr !== '');
$busy = @stream_socket_server("tcp://$address", $errno, $errstr);
var_dump($busy, $errstr);
var_dump(@stream_socket_accept($server, 0));
fclose($server);

$proc = proc_open([PHP_BINARY, '-r', 'echo fgets(STDIN); usleep(200000); echo "late";'], [['pipe', 'r'], ['pipe', 'w']], $pipes);
stream_set_blocking($pipes[1], false);
var_dump(stream_get_meta_data($pipes[1])['blocked'], array_key_exists('uri', stream_get_meta_data($pipes[1])));
fwrite($pipes[0], "line\n");
fclose($pipes[0]);
$seen = '';
while (!feof($pipes[1])) {
    $read = [$pipes[1]];
    $write = $except = null;
    stream_select($read, $write, $except, 5);
    $seen .= fread($pipes[1], 8192);
}
var_dump($seen, proc_close($proc));
