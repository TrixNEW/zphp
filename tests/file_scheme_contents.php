<?php

$file = sys_get_temp_dir() . '/zphp_file_scheme_' . getmypid() . '.txt';
file_put_contents($file, 'contents');
echo file_get_contents('file://' . $file), "\n";
unlink($file);
