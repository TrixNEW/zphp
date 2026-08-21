<?php
// closed native handles release resources and reuse object shells in persistent CLI loops
$before = memory_get_usage();
for ($i = 0; $i < 10000; $i++) {
    $file = fopen('php://temp', 'w+');
    fwrite($file, 'x');
    fclose($file);

    $reader = new XMLReader();
    $reader->XML('<root/>');
    $reader->close();

    $writer = new XMLWriter();
    $writer->openMemory();

    $curl = curl_init();
    curl_close($curl);

    unset($file, $reader, $writer, $curl);
}
$growth = memory_get_usage() - $before;
echo ($growth < 12 * 1024 * 1024) ? "bounded\n" : "unbounded\n";
