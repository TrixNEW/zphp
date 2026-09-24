<?php
$f = fopen(__FILE__, 'r');
echo serialize($f), "\n";
echo serialize([$f, $f, 'k' => [$f]]), "\n";
var_dump(unserialize(serialize(['stream' => $f, 'n' => 1])));
$forged = unserialize('O:10:"FileHandle":2:{s:4:"__fd";i:0;s:6:"__open";b:1;}');
var_dump(get_class($forged), is_resource($forged));
var_dump(fread($f, 5));
fclose($f);
