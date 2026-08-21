<?php
// native database, image, and parser objects release backing state in persistent CLI loops
$before = memory_get_usage();
for ($i = 0; $i < 5000; $i++) {
    $db = new PDO('sqlite::memory:');
    $statement = $db->prepare('select 1');
    $statement->execute();
    $statement->closeCursor();

    $image = imagecreatetruecolor(4, 4);
    imagedestroy($image);

    $parser = xml_parser_create();
    xml_parser_free($parser);

    unset($statement, $db, $image, $parser);
}
$growth = memory_get_usage() - $before;
echo ($growth < 12 * 1024 * 1024) ? "bounded\n" : "unbounded\n";
